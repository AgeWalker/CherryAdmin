<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// вывод списка основных элементов
if ($action=="list") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT $table_name.*, mod_admin.username FROM `$table_name` LEFT JOIN `mod_admin` ON (mod_admin.id=$table_name.edit_id) ORDER BY `parent`,`rank`";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[$lRes['parent']][] = $lRes;
    $data = getTree($data, 0);

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
            <table class="table table-hover">
            <tr>
              <th width="2%"><h4>ID</h4></th>
              <th><h4>Название</h4></th>
              <th><h4>Последняя редакция</h4></th>
              <th width="10%"><h4>Позиция</h4></th>
              <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
            </tr>
            '.getTreeTable($data, '', $mod).'  
            <tr>
              <td colspan="4"></td>
              <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
            </tr>
            </table>
          </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Страниц не создано</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div>';
}

// редактирование
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
      $positions_arr = getEditformPositions($edit_form_fields, array('left', 'left_two','right','bottom','hidden'));
      
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
  else //обрабатываем форму
  {
    echo '<p>Обработка информации, пожалуйста подождите...</p>';
    //подготавливаем данные
    $preparedData = $_POST;

    unset($preparedData['oldparent']);  

		
//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        createImages($source,$rand_name,$image_props[$mod]['page']);
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
	
//=== сохранение файла 1
    if(!isset($_POST['file_load'])) {
      if(!empty($_FILES["file"]["name"])) {
        $source=$_FILES["file"]["tmp_name"];
        $rand_name = trans(substr($_FILES["file"]["name"],0,-4));
        $type = explode('.',$_FILES["file"]["name"]);
        $type = array_pop($type);
        $rand_name .= '.'.$type;
        move_uploaded_file($source,$_SERVER['DOCUMENT_ROOT'].'/upload/source/'.$rand_name);
        $rand_name = '/upload/source/'.$rand_name;
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['file_load'];
    }
    $preparedData['file'] = $rand_name;
    unset($preparedData['file_load']);
//=== /сохранение файла 1

//=== сохранение файла 2
    if(!isset($_POST['file_two_load'])) {
      if(!empty($_FILES["file_two"]["name"])) {
        $source=$_FILES["file_two"]["tmp_name"];
        $rand_name = trans(substr($_FILES["file_two"]["name"],0,-4));
        $type = explode('.',$_FILES["file_two"]["name"]);
        $type = array_pop($type);
        $rand_name .= '.'.$type;
        move_uploaded_file($source,$_SERVER['DOCUMENT_ROOT'].'/upload/source/'.$rand_name);
        $rand_name = '/upload/source/'.$rand_name;
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['file_two_load'];
    }
    $preparedData['file_two'] = $rand_name;
    unset($preparedData['file_two_load']);
//=== /сохранение файла 2

//=== сохранение файла 3
    if(!isset($_POST['file_three_load'])) {
      if(!empty($_FILES["file_three"]["name"])) {
        $source=$_FILES["file_three"]["tmp_name"];
        $rand_name = trans(substr($_FILES["file_three"]["name"],0,-4));
        $type = explode('.',$_FILES["file_three"]["name"]);
        $type = array_pop($type);
        $rand_name .= '.'.$type;
        move_uploaded_file($source,$_SERVER['DOCUMENT_ROOT'].'/upload/source/'.$rand_name);
        $rand_name = '/upload/source/'.$rand_name;
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['file_three_load'];
    }
    $preparedData['file_three'] = $rand_name;
    unset($preparedData['file_three_load']);
//=== /сохранение файла 3

//=== сохранение файла 4
    if(!isset($_POST['file_four_load'])) {
      if(!empty($_FILES["file_four"]["name"])) {
        $source=$_FILES["file_four"]["tmp_name"];
        $rand_name = trans(substr($_FILES["file_four"]["name"],0,-4));
        $type = explode('.',$_FILES["file_four"]["name"]);
        $type = array_pop($type);
        $rand_name .= '.'.$type;
        move_uploaded_file($source,$_SERVER['DOCUMENT_ROOT'].'/upload/source/'.$rand_name);
        $rand_name = '/upload/source/'.$rand_name;
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['file_four_load'];
    }
    $preparedData['file_four'] = $rand_name;
    unset($preparedData['file_four_load']);
//=== /сохранение файла 4
//=== сохранение файла 5
    if(!isset($_POST['file_one_load'])) {
      if(!empty($_FILES["file_one"]["name"])) {
        $source=$_FILES["file_one"]["tmp_name"];
        $rand_name = trans(substr($_FILES["file_one"]["name"],0,-4));
        $type = explode('.',$_FILES["file_one"]["name"]);
        $type = array_pop($type);
        $rand_name .= '.'.$type;
        move_uploaded_file($source,$_SERVER['DOCUMENT_ROOT'].'/upload/source/'.$rand_name);
        $rand_name = '/upload/source/'.$rand_name;
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['file_one_load'];
    }
    $preparedData['file_one'] = $rand_name;
    unset($preparedData['file_one_load']);
//=== /сохранение файла 5

	
    if (empty($_POST["rank"]) or $_POST["parent"]!=$_POST["oldparent"]) {
      $Db->query="SELECT COUNT(id) FROM `$table_name` WHERE `parent`='".$_POST["parent"]."'";
      $Db->query();
      $lRes=mysql_fetch_assoc($Db->lQueryResult);
      $preparedData['rank'] = $lRes['COUNT(id)']+1;
    }

    if ($preparedData['id']<1) {
      $preparedData['anchor'] = trans($preparedData['name']);
    } else {
      if ($preparedData['id']==1) $preparedData['anchor'] = "index";
      elseif ($preparedData['anchor']=='') $preparedData['anchor'] = trans($preparedData['name']);
    }

    $preparedData['edit_date'] = 'NOW()';
    $preparedData['edit_id'] = $global_user;

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('text','redirect');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);
    
    //альтернативная обработка для additional_images_two
		if (!empty($_FILES["additional_images"]["name"])) {
      $fileElementName = 'additional_images';
      $i = 0;
      $msg = "";
      $msg_full = "";
      $files_count = sizeof($_FILES[$fileElementName]["name"]);
      for ($i = 0; $i < $files_count; $i++) {	
        if(empty($_FILES[$fileElementName]['tmp_name'][$i]) || $_FILES[$fileElementName]['tmp_name'][$i] == 'none') {	
        $msg = "";
        }
        else {
          $myname = rand();
          createImages($_FILES[$fileElementName]['tmp_name'][$i],$myname,$image_props[$mod]['album_photo']);
          $msg.= $myname."|";
          @unlink($_FILES[$fileElementName][$i]);
        }
      }
      if (!empty($msg)) {
        $file = explode("|", substr($msg,0,-1));
        $input = "";
        foreach ($file as $key=>$value) $input.= "('".$value."','".$id."'),";
        $input = substr($input,0,-1);
        $Db->query="INSERT INTO `mod_content_file` (`name`,`item`) VALUES ".$input;
        $Db->query();
      }	
    }
	
	
    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = mysql_insert_id();
        $name = $preparedData['name'];
        writeStat('Добавлена новая страница <a href="index.php?mod='.$mod.'&action='.$action.'&id='.$new_id.'" class="timeline_link_item">"'.$name.'"</a>','2');
      }
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
    }
  }
}
///////////////////////////// ПАРТНЕРЫ  //////////////////////////////////////////////////////
$table_name = "mod_content_partners";

if ($action=="list_partners") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT * FROM `$table_name` ORDER BY `id` DESC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult))  {
      $data[0][] = $lRes;
    }
	
	$data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('id','name','conf');
	$extra_array = array('action_postfix'=>'_partners');

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet_partners" name="form1">
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
		$content_mod .= '<p class="have_no_item">Партнеров нет</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div><a href="index.php?mod=content&action=edit_partners&id=new" class="btn btn-info">Добавить</a>';
}

// редактирование
if ($action=="edit_partners") {
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
  else //обрабатываем форму
  {
    echo '<p>Обработка информации, пожалуйста подождите...</p>';
    //подготавливаем данные
    $preparedData = $_POST;   
    
//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        $image_type = substr($_FILES["cover"]["name"], -3, 3);
        createImages($source,$rand_name,$image_props[$mod]['partners'],$image_type);
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
	
    //if (empty($preparedData["anchor"])) 
    //  $preparedData["anchor"] = trans($preparedData["name"]);

    //$preparedData['edit_date'] = 'NOW()';
    //$preparedData['edit_id'] = $global_user;

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('description','anons');
    $saveStatus = saveEditForm($Db,$table_name,$preparedData,$filterExceptions);
    
    if ($saveStatus==true) {
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list_partners'></head></html>");
    }
  }
}


if ($action=="delete_cover_partners") {
	$Db->query="SELECT `cover` FROM `mod_content_partners` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_content_partners` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['cat'] as $v)
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=content&action=edit_partners&id=".$id."'></head></html>");
}

///////////////////////////// СЕРТИФИКАТЫ  //////////////////////////////////////////////////////


$table_name = "mod_content_sertif";

if ($action=="list_sertif") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT * FROM `$table_name` ORDER BY `id` DESC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult))  {
      $data[0][] = $lRes;
    }
	
	$data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('id','name', 'conf');
	$extra_array = array('action_postfix'=>'_sertif');

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet_sertif" name="form1">
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
		$content_mod .= '<p class="have_no_item">Сертификатов нет</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div><a href="index.php?mod=content&action=edit_sertif&id=new" class="btn btn-info">Добавить</a>';
}

// редактирование
if ($action=="edit_sertif") {
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
  else //обрабатываем форму
  {
    echo '<p>Обработка информации, пожалуйста подождите...</p>';
    //подготавливаем данные
    $preparedData = $_POST;   
    
//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        $image_type = substr($_FILES["cover"]["name"], -3, 3);
        createImages($source,$rand_name,$image_props[$mod]['sertif'],$image_type);
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
	
    //if (empty($preparedData["anchor"])) 
    //  $preparedData["anchor"] = trans($preparedData["name"]);

    //$preparedData['edit_date'] = 'NOW()';
    //$preparedData['edit_id'] = $global_user;

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('description','anons');
    $saveStatus = saveEditForm($Db,$table_name,$preparedData,$filterExceptions);
    
    if ($saveStatus==true) {
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list_sertif'></head></html>");
    }
  }
}


if ($action=="delete_cover_sertif") {
	$Db->query="SELECT `cover` FROM `mod_content_sertif` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_content_sertif` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['cat'] as $v)
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=content&action=edit_sertif&id=".$id."'></head></html>");
}

// удаление дополнительных изображений
if ($action=="delete_image_album_photo") {
	$Db->query="SELECT `name`,`item` FROM `mod_content_file` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="DELETE FROM `mod_content_file` WHERE `id`='$id'";
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['album_photo'] as $v)
    if (!empty($lRes["name"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["name"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["name"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=".$lRes['item']."'></head></html>");
}

// Удаление файла 1
if ($action=="delete_file_file") {
	$Db->query="SELECT `file` FROM `mod_content` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_content` SET `file`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  if (!empty($lRes["file"]) && file_exists($_SERVER['DOCUMENT_ROOT'].$lRes['file']))
    unlink($_SERVER['DOCUMENT_ROOT'].$lRes['file']);

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=".$id."'></head></html>");
}

// Удаление файла 2
if ($action=="delete_file_file_two") {
	$Db->query="SELECT `file_two` FROM `mod_content` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_content` SET `file_two`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  if (!empty($lRes["file_two"]) && file_exists($_SERVER['DOCUMENT_ROOT'].$lRes['file_two']))
    unlink($_SERVER['DOCUMENT_ROOT'].$lRes['file_two']);

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=".$id."'></head></html>");
}

// Удаление файла 3
if ($action=="delete_file_file_three") {
	$Db->query="SELECT `file_three` FROM `mod_content` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_content` SET `file_three`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  if (!empty($lRes["file_three"]) && file_exists($_SERVER['DOCUMENT_ROOT'].$lRes['file_three']))
    unlink($_SERVER['DOCUMENT_ROOT'].$lRes['file_three']);

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=".$id."'></head></html>");
}
