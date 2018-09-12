<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");

$table_name = 'mod_slider_two';

if ($action=="edit") {
  
  if (!@$_POST["submit"]) {
    //получаем параметры материала, если это редактирование
    if (isset($id)) {
      $Db->query="SELECT * FROM `$table_name` WHERE id='$id' LIMIT 1";
      $Db->query();
      $edit_parameters = mysql_fetch_assoc($Db->lQueryResult);
    }

    //получаем комментарии к полям таблицы и генерируем на их основе элементы формы создания/редактирования
    $Db->query="SHOW FULL COLUMNS FROM `$table_name`";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) { 
      //формируем массив с готовыми элементами формы
      $edit_form_fields = array();
      while ($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
        if (!empty($lRes['Comment'])) {
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
    
//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        createImages($source,$rand_name,$image_props[$mod]['slider']);
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['cover_load'];
    }
    $preparedData['cover'] = $rand_name;
    unset($preparedData['cover_load']);
//=== /сохранение обложки товара    


    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('link', 'text');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);

    if ($saveStatus==true) {
      
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
    } 
	}
}


if ($action=="list") {
  $table_name = 'mod_slider_two';
  $content_mod = '
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-danger">
          <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT `id`, `name`, `act` FROM `$table_name` ORDER BY `rank`";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //какие поля выводить
    $fields_to_table = array('id','name','conf');
    $extra_array = array('action_postfix'=>'');

    $content_mod .= '
            <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
              <table class="table table-hover">
                <tr>
                  <th width="2%"><h4>ID</h4></th>
                  <th><h4>Название</h4></th>
                  <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
                </tr>
                '.getTreeTable($data, '', $mod, $fields_to_table, $extra_array).'
                <tr>
                  <td colspan="2"></td>
                  <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
                </tr>
              </table>
            </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Слайдеров нет</p>';
  }
  
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>';
}

// обновление активности или удаление админов
if ($action=='pereschet') {  
  if(!empty($_POST["delete"])) {
    $query = '('.implode(',',array_keys($_POST["delete"])).')';
    $Db->query="DELETE FROM `$table_name` WHERE `id` IN ".$query;
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

if ($action=="delete_cover_slider") {
	$Db->query="SELECT `cover` FROM `$table_name` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `$table_name` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['slider'] as $v)
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=slider_two&action=edit&id=".$id."'></head></html>");
}