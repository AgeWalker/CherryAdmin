<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// вывод списка основных элементов
if ($action=="list") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT $table_name.*, mod_admin.username FROM `$table_name` LEFT JOIN `mod_admin` ON (mod_admin.id=$table_name.edit_id) ORDER BY `id` DESC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('id','name','date','conf');

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
            <table class="table table-hover">
            <tr>
              <th width="2%"><h4>ID</h4></th>
              <th><h4>Название</h4></th>
              <th><h4>Последняя редакция</h4></th>
              <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
            </tr>
            '.getTreeTable($data, '', $mod, $fields_to_table).'  
            <tr>
              <td colspan="3"></td>
              <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
            </tr>
            </table>
          </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Статей не добавлено</p>';
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
      $positions_arr = getEditformPositions($edit_form_fields, array('left','right','bottom','hidden'));

      // посмотреть на сайте, только при редактировании
      if ($edit_parameters['id']!='') {
        $Db->query = "SELECT `id`,`anchor` FROM `mod_articles_cat` WHERE `id`='{$edit_parameters['parent']}' LIMIT 1";
        $Db->query();
        if (mysql_num_rows($Db->lQueryResult)>0) {
          $art_parent = mysql_fetch_assoc($Db->lQueryResult);
          $art_parent = $art_parent['id'].'-'.$art_parent['anchor'];
        }

        $link_on_site = '
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <a href="/'.$mod.'/'.$edit_parameters['id'].'-'.$edit_parameters['anchor'].'.html" target="_blank">посмотреть на сайте</a>
              </div><!-- /.box-body -->
            </div>
          </div>';
      }
      
      //генерируем внешний вид формы редактирования
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">
          
          '.$link_on_site.'
          
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
    
	 $parent_multiselect = $preparedData['parent']; 
    unset($preparedData["parent"]);
	  
//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        //$image_type = substr($_FILES["cover"]["name"], -3, 3);
        //createImages($source,$rand_name,$image_props[$mod]['article'],$image_type);
		createImages($source,$rand_name,$image_props[$mod]['article']);
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
//=== сохранение обложки товара
    if(!isset($_POST['cover_two_load'])) {
      if(!empty($_FILES["cover_two"]["name"])) {
        $source=$_FILES["cover_two"]["tmp_name"];
        $rand_name = rand();
        $image_type = substr($_FILES["cover_two"]["name"], -3, 3);
        createImages($source,$rand_name,$image_props[$mod]['article_two'],$image_type);
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['cover_two_load'];
    }
    $preparedData['cover_two'] = $rand_name;
    unset($preparedData['cover_two_load']);
//=== /сохранение обложки товара

    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);

    $preparedData['edit_date'] = 'NOW()';
    $preparedData['edit_id'] = $global_user;
    
    //определяем ранг если была смена категории
    if(empty($_POST["rank"]) or $_POST["parent"]!=$_POST["oldparent"]) {
      $Db->query = "SELECT COUNT(id) FROM `$table_name` WHERE `parent`='".$_POST["parent"]."'";
      $Db->query();
      $lRes = mysql_fetch_assoc($Db->lQueryResult);
      $preparedData["rank"] = $lRes['COUNT(id)']+1;
    }
    unset($preparedData["oldparent"]);

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('text','anons');
    $saveStatus = saveEditForm($Db,$table_name,$preparedData,$filterExceptions);
	  
	 //альтернативная обработка для select_multiple
    if (count($parent_multiselect)>0) {
      $table_name_n = $table_name."_relations";
      $Db->query="DELETE FROM `$table_name_n` WHERE `item`='$id'";
      $Db->query();

      $relations_values = '';
      foreach ($parent_multiselect as $v) {
        $relations_values .= "('$id','$v'),";
      }
      $relations_values = clearLastComma($relations_values);      
      $Db->query = "INSERT INTO `$table_name_n` (`item`,`rel`) VALUES $relations_values";
      $Db->query();
    }    
    
    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = mysql_insert_id();
        $name = $preparedData['name'];
        writeStat('Добавлена новая статья <a href="index.php?mod='.$mod.'&action='.$action.'&id='.$new_id.'" class="timeline_link_item">"'.$name.'"</a>','2');
      }
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
    }
  }
}

if ($action=="delete_cover_article") {
	$Db->query="SELECT `cover` FROM `mod_articles` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_articles` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['article'] as $v) {
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");
  }
	
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=articles&action=edit&id=".$id."'></head></html>");
}
if ($action=="delete_cover_article_two") {
	$Db->query="SELECT `cover_two` FROM `mod_articles` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_articles` SET `cover_two`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['article_two'] as $v) {
    if (!empty($lRes["cover_two"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");
  }
	
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=articles&action=edit&id=".$id."'></head></html>");
}

if ($action=="edit_cat") {
  $table_name = 'mod_'.$mod.'_cat';
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
      
      // посмотреть на сайте, только при редактировании
      if ($edit_parameters['id']!='') {
        $link_on_site = '
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <a href="/'.$mod.'/'.$edit_parameters['id'].'-'.$edit_parameters['anchor'].'/" target="_blank">посмотреть на сайте</a>
              </div><!-- /.box-body -->
            </div>
          </div>';
      }
      
      //генерируем внешний вид формы редактирования
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">
        
          '.$link_on_site.'

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
    
    //генерируем новый якорь если якоря нет
    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);

	//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        $image_type = substr($_FILES["cover"]["name"], -3, 3);
        createImages($source,$rand_name,$image_props[$mod]['article'],$image_type);
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
    $filterExceptions = array('text');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);

    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = mysql_insert_id();
        $name = $preparedData['name'];
        writeStat('Добавлена новая категория статей <a href="index.php?mod='.$mod.'&action='.$action.'&id='.$new_id.'" class="timeline_link_item">"'.$name.'"</a> для статей','2');
      }

      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list_cat'></head></html>");
    } 
	}
}

if ($action=="list_cat") {
  $table_name = 'mod_'.$mod.'_cat';
  $content_mod = '
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-danger">
          <div class="box-body table-responsive no-padding">';
		  $data = '';
  $Db->query="SELECT `id`, `name`, `parent`, `act`, `rank` FROM `$table_name` ORDER BY `parent`,`rank`";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[$lRes['parent']][] = $lRes;
	  $data = getTree($data, 0);
	
    //какие поля выводить
    $fields_to_table = array('id','name','rank','conf');
    $extra_array = array('action_postfix'=>'_cat');

    $content_mod .= '
            <form method="post" action="index.php?mod='.$mod.'&action=pereschet_cat" name="form1">
              <table class="table table-hover">
                <tr>
                  <th width="2%"><h4>ID</h4></th>
                  <th><h4>Название</h4></th>
                  <th width="10%"><h4>Позиция</h4></th>
                  <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
                </tr>
                '.getTreeTable($data, '', $mod, $fields_to_table, $extra_array).'
                <tr>
                  <td colspan="3"></td>
                  <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
                </tr>
              </table>
            </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Категорий нет</p>';
  }
  
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>';
}
