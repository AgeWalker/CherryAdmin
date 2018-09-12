<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
if ($action=="edit") {
  $table_name = 'mod_'.$mod;  
  if (!@$_POST["submit"]) {
    //получаем параметры материала, если это редактирование
    if (isset($id)) {
      $Db->query="SELECT * FROM `$table_name` WHERE id='$id' LIMIT 1";
      $Db->query();
      $edit_parameters = mysql_fetch_assoc($Db->lQueryResult);
    }

    //записываем все параметры коллбэка в сессию
    if (!isset($_SESSION['admin_callbacks'])) $_SESSION['admin_callbacks'] = array();
    if (isset($page_callback))
      $_SESSION['admin_callbacks']['page'] = $page_callback;

    //получаем комментарии к полям таблицы и генерируем на их основе элементы формы создания/редактирования
    $Db->query="SHOW FULL COLUMNS FROM `$table_name`";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) { 
      //формируем массив с готовыми элементами формы
      $edit_form_fields = array();
      while ($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
        if (!empty($lRes['Comment'])) {
          $lRes = array(
            'Field' => $lRes['Field'],
            'Comment' => $lRes['Comment']
          );
          $edit_form_fields[$lRes['Field']] = getEditformFieldView($lRes,$edit_parameters,$Db,$mod);
        }
      }

      //включаем CKEditor
      $editor = 1;

      //сортируем все поля по их позициям
      $positions_arr = getEditformPositions($edit_form_fields, array('left','right','bottom','hidden'));

      //генерируем внешний вид формы редактирования
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">

          <div class="col-md-6">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['left']).'
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-6">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['right']).'
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['bottom']).'
              </div><!-- /.box-body -->
              <div class="box-footer">
                '.implode($positions_arr['hidden']).'
                <input type="submit" class="btn btn-danger pull-right" value="Сохранить" name="submit" />
              </div>
            </div>
          </div>

        </form><!-- /.box -->
      </div>';
    }
  }
	else {
    echo '<p>Обработка информации, пожалуйста подождите...</p>';
    //подготавливаем данные
    $preparedData = $_POST;

    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('email','manager_comment');
    $saveStatus = saveEditForm($Db,$table_name,$preparedData,$filterExceptions);

    if ($preparedData['id']>0) $id = $preparedData['id'];
    else $id = mysql_insert_id();

    if ($saveStatus==true) {      
      if (count($_SESSION['admin_callbacks'])>0) {
        $callback_params = '';
        foreach ($_SESSION['admin_callbacks'] as $param=>$value) {
          $callback_params .= "&$param=$value";
        }
        unset($_SESSION['admin_callbacks']);
      }

      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list$callback_params'></head></html>");
    }
	}
}

//TODO: позже добавить вывод изображения товаров
if ($action=="list") {
  $content_mod = '
  <div class="row">
    <div class="col-xs-12">
      <div class="box box-danger">
      <div class="box-body table-responsive no-padding">'; 

  $per_page = 20; // кол-во выводимых на страницу
  $Db->query="SELECT COUNT($table_name.id) as `count` FROM $table_name"; 
  $Db->query();
  $lRes=mysql_fetch_assoc($Db->lQueryResult);
  $count = $lRes["count"];
  $total = (($count - 1) / $per_page) + 1;
  $total =  intval($total);
  $page = intval($page);  
  if(empty($page) or $page < 0) 
    $page = 1;
  if($page > $total) 
    $page = $total;
  $start = $page * $per_page - $per_page;
  if ($start<0) 
    $start = 0;
  
  $Db->query="SELECT `id`, `name`, `summ`, `date` FROM `mod_order` ORDER BY `date` DESC LIMIT $start, $per_page";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[$lRes['id']] = $lRes;

//  какие поля выводить
    $fields_to_table = array('id','name','summ','date_only','delete');

    // пагинация
    if ($total>1) {
      for ($i=1; $i<=$total; $i++) {
        if ( ($i==1) || ($i==$total) || ($i==($page-1)) || ($i==$page) || ($i==($page+1)) ) {
            $navi.='<li>';
            if ($page!=$i) $navi.=' <a class="btn btn-danger" href="index.php?mod='.$mod.'&action='.$action.'&page='.$i.'">'.$i.'</a> '; 
            else $navi.= '<span class="btn btn-success">'.$i.'</span>';
            $navi.='</li>';
        }
        else {$navi.='@DOTS@';}
      }      
			$navi=explode('@',$navi);
			$navi_out='';
			foreach ($navi as $v) {
        if ($v!='') {
          if ($v=='DOTS') {
            $prev_is_dots=TRUE;
          }
          else {
            if ($prev_is_dots) 
              $navi_out.='<li><span class="dots">. . .</span></li>';
            $navi_out.=$v;$prev_is_dots=FALSE;
          }
        }
      }
			$navi=$navi_out;
      $pagination = '<ul class="in_admin_pagination list-unstyled text-center">'.$navi.'</ul>';
    }

    $content_mod .= '  
        <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
          <table class="table table-hover">
          <tr>
            <th width="2%"><h4>ID</h4></th>
            <th><h4>Покупатель</h4></th>
            <th width="20%"><h4>Сумма</h4></th>
            <th width="20%"><h4>Дата</h4></th>
            <th width="10%"><h4><i class="fa fa-trash"></i></h4></th>
          </tr>
            '.getTreeTable($data, '', $mod, $fields_to_table).'
          <tr>
            <td colspan="4">'.$pagination.'</td>
            <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
          </tr>
          </table>
          </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Заказов нет</p>';
  }
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>';
}