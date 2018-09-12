<?php
session_start();

require_once("db.php");
require_once($SITE_ROOT."/core/functions.php");
require_once('functions.php');

$Db = new Db ($DBServer,$DBLogin,$DBPassword,$DBName);
$Db->connect();
mysql_query("SET NAMES 'utf8'");
require_once("auth.php");

//парсинг строки параметров на параметры
parse_str($_SERVER['QUERY_STRING']);

// глобальные настройки дл¤ модулей
$Db->query="SELECT `mod`,`option`,`value` FROM `mod_config`";
$Db->query();
while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) {
    $config[$lRes["mod"]][$lRes["option"]] = $lRes["value"];
}

// параметры редактирования изображений
$Db->query="SELECT * FROM `images_properties`";
$Db->query();
while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
  $image_props[$lRes['module']][$lRes['item']][] = array(
  'width'=>$lRes['width'],
  'height'=>$lRes['height'],
  'folder'=>$lRes['folder'],
  'prefix'=>$lRes['prefix'],
  'function'=>$lRes['function'],
  'do_cut'=>$lRes['do_cut']  
);

// если выполнен выход
if (@$_GET['logout'] && $_GET['logout']==true)
{
	$Db->query="UPDATE `mod_admin` SET `date`=NOW() WHERE `id`='".$global_user."'";
	$Db->query();
	unset($_SESSION['ca_user']);
	unset($_SESSION['ca_hash']);
	unset($_SESSION['ca_access']);
  unset($_SESSION['KCFINDER']);
  
  writeStat('Пользователь '.$status.' вышел из системы','1');
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php'></head></html>");
}

if ($autorized != 1) {
		$body_class = "login-page";
		
		$form = '<div class="login-box">
					<div class="login-logo">
						<a href="#"><b>Cherry</b>Admin</a>
					</div><!-- /.login-logo -->
					<div class="login-box-body">
						<p class="login-box-msg">Вход в систему управления сайтом</p>
						<form method="post">
						  <div class="form-group has-feedback">
							<input type="email" class="form-control" name="email" placeholder="Email">
							<span class="glyphicon glyphicon-envelope form-control-feedback"></span>
						  </div>
						  <div class="form-group has-feedback">
							<input type="password" class="form-control" name="pass" placeholder="Пароль">
							<span class="glyphicon glyphicon-lock form-control-feedback"></span>
						  </div>
						  <div class="row">
							<div class="col-xs-8">
							  <div class="checkbox icheck">
								<label>
									<input type="checkbox"> Запомнить меня
								</label>
							  </div>
							</div><!-- /.col -->
							<div class="col-xs-4">
								<input type="submit" name="login_submit" class="btn btn-danger btn-block btn-flat" value="Войти">
							</div><!-- /.col -->
						  </div>
						</form>

					
					<!--<h6 align="center"><a href="#" class="text-red">Восстановить пароль</a></h6>-->

					</div><!-- /.login-box-body -->
					</div><!-- /.login-box -->';

		// если не нажата кнопка входа
		if (!@$_POST["login_submit"]) {
			$content = $form;
		}
		else // если нажата кнопка входа, логиним
		{
			$ip=RealIP(); // проверка по айпи
			$Db->query="DELETE FROM error_login WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date) > 900"; //удаляем предварительные данные
        	$Db->query();
			
			$Db->query="SELECT col FROM error_login WHERE ip='".$ip."'";
			$Db->query();
			$lRes=mysql_fetch_assoc($Db->lQueryResult);
			if ($lRes['col'] > 2) {
				//если ошибок больше двух, т.е три, то выдаем сообщение.
				$content = $form.'<div class="lockscreen-wrapper"><div class="alert alert-warning alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-warning"></i> Ошибка!</h4>
                    Вы набрали логин или пароль неверно 3 раза. Подождите 15 минут до следующей попытки.
                  </div></div>';
            }      
			else
			{
    			$filter = new filter; 
				if (is_email($_POST["email"])) $login_ok = $_POST["email"]; 
				else $content = $form.'<div class="lockscreen-wrapper"><div class="alert alert-warning alert-dismissable">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<h4><i class="icon fa fa-warning"></i> Ошибка!</h4>
							Введеные вами данные не верны.
					  </div></div>';
    			$pass_ok = $filter->html_filter($_POST["pass"]);
				$Db->query="SELECT * FROM `mod_admin` WHERE `mail`='".$login_ok."' AND `act`='1' LIMIT 1";
				$Db->query();
				$lRes=mysql_fetch_assoc($Db->lQueryResult);
    			if (empty($lRes['pass']))
    			{
					$Db->query="SELECT ip FROM error_login WHERE ip='".$ip."'";
					$Db->query();
					$lRes=mysql_fetch_assoc($Db->lQueryResult);
						if ($ip == $lRes[0]) {
						$Db->query="SELECT col FROM error_login WHERE ip='".$ip."'";
						$Db->query();
						$lRes=mysql_fetch_assoc($Db->lQueryResult);         
						$col = $lRes[0] + 1;
						$Db->query="UPDATE `error_login` SET `col`='".$col."',`date`=NOW() WHERE `ip`='".$ip."'";
						$Db->query();
						}          
						else 
						{
							$Db->query="INSERT INTO error_login (ip,date,col) VALUES  ('".$ip."',NOW(),'1')";
							$Db->query();
						}      
						$content = $form.'<div class="lockscreen-wrapper"><div class="alert alert-warning alert-dismissable">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<h4><i class="icon fa fa-warning"></i> Ошибка!</h4>
							Введеные вами данные не верны.
					  </div></div>';
    			}
    		else {
					$pass_solt = pass_solt($pass_ok);
					if ($lRes['pass']==$pass_solt) {				
						$hash = pass_solt(generateCode(10));
						$access = pass_solt($lRes['access']);
						$Db->query="UPDATE `mod_admin` SET `hash`='$hash' WHERE `id`='".$lRes['id']."'";
						$Db->query();            
						$_SESSION['ca_user']=$lRes['id']; 
						$_SESSION['ca_hash']=$hash;
						$_SESSION['ca_access']=$access;
						$Db->query="UPDATE `mod_admin` SET `date`=NOW() WHERE `id`='".$lRes['id']."'";
						$Db->query();
						
            writeStat('Пользователь '.$lRes['username'].' вошел в систему','1');							
						exit("<html><head><meta  http-equiv='Refresh' content='0; URL=".$_POST["link"]."'></head></html>");					
					}
					else {
						$content = $form.'<div class="lockscreen-wrapper"><div class="alert alert-warning alert-dismissable">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<h4><i class="icon fa fa-warning"></i> Ошибка!</h4>
							Введеные вами данные не верны.
					  </div></div>';
					}
    		}
			}
		}
	}
	else { 
		// выстраиваем шапку
		$content = '<div class="wrapper">
						<header class="main-header">

						<!-- Logo -->
						<a href="index.php" class="logo">
						  <!-- mini logo for sidebar mini 50x50 pixels -->
						  <span class="logo-mini"><b>Ch</b>Ad</span>
						  <!-- logo for regular state and mobile devices -->
						  <span class="logo-lg"><i class="fa fa-mouse-pointer"></i> <b>Cherry</b>Admin</span>
						</a>

						<!-- Header Navbar -->
						<nav class="navbar navbar-static-top" role="navigation">
						  <!-- Sidebar toggle button-->
						  <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
							<span class="sr-only">Свернуть</span>
						  </a>
						  <!-- Navbar Right Menu -->
						  <div class="navbar-custom-menu">
							<ul class="nav navbar-nav">
							  <li class="dropdown tasks-menu">
								<a href="/" target="_blank" title="Перейти на сайт (откроется в новом окне)">
								  <i class="fa fa-home"></i>
								</a>
							  </li>
							  <li class="dropdown tasks-menu">
								<a href="index.php?mod=config&action=common" title="Общие настройки сайта">
								  <i class="fa fa-gears"></i>
								</a>
							  </li>
							  <li class="dropdown user user-menu">
								<a href="index.php?mod=users&action=password" title="Изменить пароль текущего пользователя">
								  <span class="hidden-xs">'.$status.'</span>
								</a>
							  </li>
							  <li>
								<a href="?logout=1"><i class="fa fa-sign-out"></i></a>
							  </li>
							</ul>
						  </div>
						</nav>
						</header>';
		
		// получаем количество оповещений со статусом 0
		$Db->query = "SELECT COUNT(`id`) as `count` FROM `mod_notifications` WHERE `status`='0'";
		$Db->query();
		if (mysql_num_rows($Db->lQueryResult)>0) {
			$new_notifications = mysql_fetch_assoc($Db->lQueryResult);
			$new_notifications = (int)$new_notifications['count'];
		}
		
		// выстраиваем боковое меню
		$Db->query="SELECT * FROM `modules` 
					LEFT JOIN `modules_conf` ON (modules.id_mod=modules_conf.rel_mod)
					LEFT JOIN `modules_access` ON (modules_conf.id_conf_mod=modules_access.rel_mod_conf)
					WHERE `act_admin_mod`='1'  AND `view`='1' AND rel_user='".$global_user."' ORDER BY `rank`,`rank_conf`";
		$Db->query();

		if (mysql_num_rows($Db->lQueryResult)>0) 
		{
			$content.= '<!-- Left side column. contains the logo and sidebar -->
						  <aside class="main-sidebar">

							<!-- sidebar: style can be found in sidebar.less -->
							<section class="sidebar">

							  <!-- Sidebar Menu -->
							  <ul class="sidebar-menu">';
			$mod_name = '';
			// собираем все меню и подменю в массив
			while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
			{ 
				if (!$lRes['hide_in_menu']) 
				{
					$array_mod[$lRes['id_mod']]["pages"][$lRes['id_conf_mod']] = array($lRes['action'], $lRes['name_conf']);
					$array_mod[$lRes['id_mod']]["img"] = $lRes['img'];
					$array_mod[$lRes['id_mod']]["name_mod"] = $lRes['name_mod'];
					$array_mod[$lRes['id_mod']]["title_mod"] = $lRes['title_mod'];
				}
				$pages[] = $lRes["name_mod"];
			}
			
			//выводим меню из массива с указанием активной страницы
			foreach ($array_mod as $key=>$value)
			{
				$list = "";
				$active = 0;
				foreach ($value["pages"] as $k=>$v)
				{
					if ($value["name_mod"]==$mod && $v[0]==$action) $active = 1;
					if ($value["name_mod"]==$mod && $v[0]==$action) 
            $list.="<li class='active'><a href='index.php?mod=".$value["name_mod"]."&action=".$v[0]."'><i class='fa fa-circle'></i> ".$v[1]."</a></li>\n"; 
          else 
            $list.="<li><a href='index.php?mod=".$value["name_mod"]."&action=".$v[0]."'><i class='fa fa-circle-o'></i> ".$v[1]."</a></li>\n";
					
				}
				if ($active == 1) $open_class = "active"; else $open_class = "";
				
				$notification_label = (($value['name_mod']=='notifications')&&($new_notifications>0) ) ? '<small class="label pull-right bg-green notification_label">'.$new_notifications.'</small>' : '';
				
				$content.= '<li class="treeview '.$open_class.'"><a href="#"><i class="fa fa-'.$value["img"].'"></i> <span>'.$value["title_mod"].'</span> '.$notification_label.' <i class="fa fa-angle-left pull-right"></i></a>
						<ul class="treeview-menu">'.$list.'</ul>';
			}
			
			$content.= '</ul>';
		}
		else $pages = array();
		
		//print_r($array_mod);
		
		$content.='</section>
        <!-- /.sidebar -->
      </aside>'; 

		if (empty($action)) $action='list';

		// выстраиваем контент
		$content.= '<!-- Content Wrapper. Contains page content -->
					  <div class="content-wrapper">
						<!-- Content Header (Page header) -->
						<section class="content-header">';
		
		$result = array_unique($pages);

		if (!isset($mod) || ($mod=="") || (!in_array($mod, $result)) ) {
			$mod="main";
//			$content.= "<h1>Система администрирования сайта</h1>";
		}
		else {
			$Db->query="SELECT * FROM modules LEFT JOIN `modules_conf` ON (modules.id_mod=modules_conf.rel_mod) WHERE name_mod='".$mod."' AND action='".$action."' LIMIT 1";
			$Db->query();
			if (mysql_num_rows($Db->lQueryResult)>0) 
			{
				//if ($mod=="catalog" && $action=="list") $content.= "<div class='filter'>Фильтр</div>"; 
				$lRes=mysql_fetch_assoc($Db->lQueryResult);  
				$content.= "<h1><i class='fa fa-".$lRes['img']."'></i> ".$lRes['title_mod'];
        if ($action!='') $content .= ' <i class="fa fa-angle-double-right"></i> '.$lRes['name_conf'];
        $content .= '</h1>';
			}
			else {
				$content .= "<h1><i class='fa fa-home'></i> Система администрирования сайта</h1>";
				$content .= '<p><i class="fa-li fa fa-spinner fa-spin"></i> Обработка информации. Пожалуйста, подождите...</p>';
			}
		}	
		$content .= ' </section>

					<!-- Main content -->
					<section class="content">';
	}

	if ($autorized != 0) {
    $table_name = "mod_$mod";
    include("admin_mod/admin_mod_$mod.php");
    include("admin_mod/common_actions.php");
  }

	include "header.php";
	echo $content;
  if ($autorized != 0) echo $content_mod;
	include "footer.php";
?>