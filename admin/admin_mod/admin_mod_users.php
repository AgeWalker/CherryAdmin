<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");

$table_name = 'mod_admin';

// вывод списка основных элементов
if ($action=="list") {  
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT $table_name.id, $table_name.username as `name`, $table_name.act, $table_name.date, $table_name.mail FROM `$table_name` ORDER BY `name`";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('name','mail','last_online','conf');

    $content_mod .= '
          <form method="post" action="index.php?mod=users&action=admins_refresh" name="form1">
            <table class="table table-hover">
            <tr>
              <th><h4>Имя</h4></th>
              <th><h4>Логин</h4></th>
              <th><h4>Посл. заход</h4></th>
              <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
            </tr>
            '.getTreeTable($data, '', 'users', $fields_to_table).'  
            <tr>
              <td colspan="3"></td>
              <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
            </tr>
            </table>
          </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Пользователей не создано</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div>';
}

// обновление активности или удаление админов
if ($action=='admins_refresh') {  
  if(!empty($_POST["delete"])) {
    $query = '('.implode(',',array_keys($_POST["delete"])).')';
    $Db->query="DELETE FROM `$table_name` WHERE `id` IN ".$query;
    $Db->query();
    
    $Db->query="DELETE FROM `modules_access` WHERE `rel_user` IN ".$query;
    $Db->query();
  }

  if(!empty($_POST["act"])) {
    $case_query = '';
    foreach($_POST["act"] as $key=>$val) {
      $case_query .= " WHEN `id`='$key' THEN '$val'";
    }
    $Db->query="UPDATE `$table_name` SET `act` = CASE $case_query ELSE `act` END";
    $Db->query();
  }

  $callback_action = 'list';
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=users&action=$callback_action'></head></html>");
}

// редактирование
if ($action=="edit") {
  $Db->query="SELECT * FROM `modules` 
        LEFT JOIN `modules_conf` ON (modules.id_mod=modules_conf.rel_mod)
        WHERE `act_mod`='1' AND `act_admin_mod`='1' AND `view`='1' ORDER BY `rank`,`rank_conf`";
  $Db->query();
  $data_pages = array();
  if (mysql_num_rows($Db->lQueryResult)>0) 
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data_pages[$lRes['id_mod']][] = $lRes;
  
  if (!@$_POST["submit"]) {
    //получаем параметры материала, если это редактирование
    if (isset($id)) {
      $Db->query="SELECT * FROM `$table_name` WHERE id='$id' LIMIT 1";
      $Db->query();
      $edit_parameters = mysql_fetch_assoc($Db->lQueryResult);
    }
    
    //генерируем внешний вид формы редактирования
    $content_mod .= '
    <div class="row">
      <form role="form" method="post" enctype="multipart/form-data">

        <div class="col-md-12">
          <div class="box box-danger">
            <div class="box-body">
              <div class="form-group">
                <label class="label-control" for="username">Имя пользователя</label>
                <input class="form-control" type="text" name="username" id="username" value="'.$edit_parameters['username'].'" required />
              </div>
              <div class="form-group">
                <label class="label-control" for="mail">E-mail</label>
                <input class="form-control" type="text" name="mail" id="mail" value="'.$edit_parameters['mail'].'" required />
                <span class="help-block">Используется в качестве логина в системе</span>
              </div>';
    if ($edit_parameters['id']<1) {
      $content_mod .= '
              <div class="form-group">
                <label class="label-control" for="password">Пароль</label>
                <input class="form-control" type="text" name="password" id="password" required />
              </div>';
    }
    else {
      $content_mod .= '
              <div class="form-group">
                <label for="password_reset">
                  <input type="checkbox" id="password_reset" name="password_reset" value="1"> Сбросить пароль и выслать новый по почте
                </label>
                <input type="hidden" name="password" value="'.$edit_parameters['pass'].'" />
              </div>';
    }
    
    $act_checked = ($edit_parameters['act']==0) ? '' : 'checked';
    $content_mod .= '
              <div class="form-group">
                <label for="act">
                  <input type="hidden" name="act" value="0">
                  <input type="checkbox" id="act" name="act" value="1" '.$act_checked.'> Активность
                </label>
              </div>
            </div><!-- /.box-body -->
          </div>
        </div>

        <div class="col-md-12">
          <div class="box box-danger">
            <div class="box-body">';

		$Db->query="SELECT `rel_mod_conf` FROM `modules_access` WHERE `rel_user`='$id'";
		$Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) {
      while($lRes=mysql_fetch_assoc($Db->lQueryResult)) $acess_array[] = $lRes["rel_mod_conf"];
    }
    else $acess_array = array();

    $content_mod .= '<div class="row"><div class="col-md-2">';
    $modules_count = 0;
		foreach ($data_pages as $key=>$value) {
      if ($modules_count==2) {
        $content_mod .= '</div><div class="col-md-2">';
        $modules_count = 0;
      }
			$content_mod.= '<h4>'.$value[0]["title_mod"].'</h4><div class="form-group">';
			foreach ($value as $k=>$v) {
        $thisChecked = (in_array($v["id_conf_mod"],$acess_array)) ? 'checked' : '';
        $content_mod .= '
        <label for="chek['.$v["id_conf_mod"].']">
          <input type="hidden" name="chek['.$v["id_conf_mod"].']" value="0">
          <input type="checkbox" id="chek['.$v["id_conf_mod"].']" name="chek['.$v["id_conf_mod"].']" value="1" '.$thisChecked.'> '.$v["name_conf"].'
        </label>
        <br/>';
			}
      $content_mod .= '</div>';
      $modules_count++;
		}
    $content_mod .= '</div></div>';

    $content_mod .= '              
            </div><!-- /.box-body -->
          </div>
        </div>

        <div class="col-md-12">
          <div class="box box-danger">
            <div class="box-footer">
              <input type="hidden" name="id" value="'.$edit_parameters['id'].'" />
              <input type="submit" class="btn btn-danger pull-right" value="Сохранить" name="submit" />
            </div>
          </div>
        </div>

      </form><!-- /.box -->
    </div>';
  }
  else //обрабатываем форму
  {
   echo '<p>Обработка информации, пожалуйста подождите...</p>';
    //подготавливаем данные
    $preparedData = $_POST;

    if (is_email($_POST["mail"])) $preparedData['mail'] = $_POST["mail"]; else $preparedData['mail'] = "";

		if ($preparedData["password_reset"]==1) {
			if (!empty($preparedData['mail'])) {
				$rand = rand();
				$pass = pass_solt($rand);
				/*$objMail = new sent_mail();
				$objMail->to = array($preparedData['mail']);
				$objMail->from = $MailRobot;
				$objMail->subject = 'Новый пароль к админ-панели сайта '.$SiteName;
				$objMail->body = 'Ваш новый пароль: '.$rand.'.';
				$objMail->send();*/
				
				$headers = 'From: ' .$MailRobot. "\r\n";
				$headers.= 'MIME-Version: 1.0' . "\r\n";
				$headers.= 'Content-type: text/html; charset=utf-8' . "\r\n";
				$subject = 'Новый пароль к админ-панели сайта '.$SiteName;
				$body .= 'Ваш новый пароль: '.$rand.'.';
				
				if (mail($preparedData['mail'], $subject, $body, $headers))
					{
						//$form_data['success'] = true;
					}
				else
					{
						echo 'Ошибка отправки письма';
					}			
			}
			else $pass = $preparedData["password"];
		}
		else $pass = $preparedData["password"];

    if ($preparedData['id']<1) {
      $pass = pass_solt($preparedData['password']);
    }

    $preparedData['pass'] = $pass;
    unset($preparedData['password']);
    unset($preparedData['password_reset']);

    unset($preparedData['chek']);

    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('mail');
    $saveStatus = saveEditForm($Db,'mod_admin',$preparedData,$filterExceptions);    

    if ($preparedData['id']<1) $id = mysql_insert_id();
    else $id = $preparedData['id'];
		
		foreach ($_POST["chek"] as $chek_id=>$chek_value) $chek[$chek_id]=$chek_value;
		
    $Db->query="SELECT `rel_mod_conf` FROM `modules_access` WHERE `rel_user`='$id'";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) {
      while($lRes=mysql_fetch_assoc($Db->lQueryResult)) $acess_array[] = $lRes["rel_mod_conf"];
    }
    else $acess_array = array();
		
		foreach ($data_pages as $key=>$value) {
			foreach ($value as $k=>$v) {
        if (in_array($v["id_conf_mod"],array_keys($chek)) && $chek[$v["id_conf_mod"]]!=0) {
					if(!in_array($v["id_conf_mod"],$acess_array))
					{
						$Db->query="INSERT INTO `modules_access` (rel_mod_conf,rel_user) VALUES  ('".$v["id_conf_mod"]."','".$id."')";
						$Db->query();
					}
				}
				else {
					if(in_array($v["id_conf_mod"],$acess_array))
					{
						$Db->query="DELETE FROM `modules_access` WHERE `rel_mod_conf`='".$v["id_conf_mod"]."' AND `rel_user`='".$id."'";
						$Db->query();
					}
				}
			}
    }
    
    if ($saveStatus==true) exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=users&action=list'></head></html>");
  }
}

if ($action=="password") {
  if (!$_POST["submit"]) {
    $Db->query="SELECT * FROM `mod_admin` WHERE `id`='$global_user' LIMIT 1";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) {
      $edit_parameters=mysql_fetch_assoc($Db->lQueryResult);
      
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <div class="form-group">
                  <label class="label-control" for="mypass">Текущий пароль</label>
                  <input class="form-control" type="password" name="mypass" id="mypass" value="" required />
                </div>
                <div class="form-group">
                  <label class="label-control" for="password">Новый пароль</label>
                  <input class="form-control" type="password" name="password" id="password" value="" required />
                </div>
                <div class="form-group">
                  <label class="label-control" for="password2">Новый пароль повторно</label>
                  <input class="form-control" type="password" name="password2" id="password2" value="" required />
                </div>
              </div><!-- /.box-body -->
              <div class="box-footer">
                <input type="submit" class="btn btn-danger pull-right" value="Сохранить" name="submit" />
              </div>
            </div>
          </div>
        </form>
      </div>';
    }
    else {
      $content_mod.= '
      <div class="row">
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <p>Неверные данные аккаунта</p>
              </div>
            </div>
          </div>
      </div>';
    }
  }
  else {
    // обрабатываем форму

    $filter = new filter; 
    $mypass = $filter->html_filter($_POST["mypass"]);
    $password = $filter->html_filter($_POST["password"]);
    $password2 = $filter->html_filter($_POST["password2"]);
    $Db->query="SELECT `pass`,`mail` FROM `mod_admin` WHERE `id`='$global_user' LIMIT 1";
    $Db->query();
    $lRes=mysql_fetch_assoc($Db->lQueryResult);
    $pass_solt = pass_solt($mypass);
    if ($lRes['pass']==$pass_solt) {
        if (!empty($password)) {
          if ($password==$password2) { 
            $update_pass = pass_solt($password);
            $logout = 1;
          }
          else {
            $content_mod.= '
            <div class="row">
                <div class="col-md-12">
                  <div class="box box-danger">
                    <div class="box-body">
                      <p>Новые пароли не совпадают!</p>
                    </div>
                  </div>
                </div>
            </div>';
          }
        }
    }
    else {
      $content_mod.= '
      <div class="row">
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <p>Неверный текущий пароль!</p>
              </div>
            </div>
          </div>
      </div>';
    }
    
    if ($logout == 1) {
      $Db->query="UPDATE `mod_admin` SET `pass`='$update_pass' WHERE `id` = '$global_user'";
      $Db->query();
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?logout=true'></head></html>"); 
    }
  }
}