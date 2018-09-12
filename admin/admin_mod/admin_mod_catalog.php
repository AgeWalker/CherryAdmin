<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// ===== КАТЕГОРИИ =====
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
        createImages($source,$rand_name,$image_props[$mod]['cat']);
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
        createImagestwo_png($source,$rand_name,$image_props[$mod]['cat']);
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
	
	
    //генерируем новый якорь если якоря нет
    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);
    
    //определяем ранг если была смена категории
    /*if(empty($_POST["rank"]) or $_POST["article"]!=$_POST["oldparent"]) {
      $Db->query = "SELECT COUNT(id) FROM `$table_name` WHERE `article`='".$_POST["article"]."'";
      $Db->query();
      $lRes = mysql_fetch_assoc($Db->lQueryResult);
      $preparedData["rank"] = $lRes['COUNT(id)']+1;
    }*/
    unset($preparedData["oldparent"]);

    //дублируем данные для того, чтобы после создания товара отдельно соотнести все категории для него
      //сохранение для select_multiple
      $parent_multiselect = $preparedData['articles']; 
      unset($preparedData["articles"]);
    // /дублируем данные для того, чтобы после создания товара отдельно соотнести все категории для него

    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('description', 'features', 'video', 'text');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);

    if ($preparedData['id']>0) $id = $preparedData['id'];
    else $id = mysql_insert_id();

    //альтернативная обработка для select_multiple
    $table_name_relations = $table_name."_relations_articles";
    $Db->query="DELETE FROM `$table_name_relations` WHERE `item`='$id'";
    $Db->query();
    if (count($parent_multiselect)>0) {
      $relations_values = '';
      foreach ($parent_multiselect as $v) {
        $relations_values .= "('$id','$v'),";
      }
      $relations_values = clearLastComma($relations_values);      
      $Db->query = "INSERT INTO `$table_name_relations` (`item`,`rel`) VALUES $relations_values";
      $Db->query();
    }

    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = mysql_insert_id();
        $name = $preparedData['name'];
        writeStat('Добавлена новая категория <a href="index.php?mod='.$mod.'&action='.$action.'&id='.$new_id.'" class="timeline_link_item">"'.$name.'"</a>','2');
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

if ($action=="delete_cover_cat") {
	$Db->query="SELECT `cover` FROM `mod_catalog_cat` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog_cat` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['cat'] as $v)
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit_cat&id=".$id."'></head></html>");
}
if ($action=="delete_cover_two_cat") {
	$Db->query="SELECT `cover_two` FROM `mod_catalog_cat` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog_cat` SET `cover_two`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['cat'] as $v)
    if (!empty($lRes["cover_two"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover_two"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover_two"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit_cat&id=".$id."'></head></html>");
}

if ($action=="delete_file_pricelist") {
	$Db->query="SELECT `price` FROM `mod_catalog_cat` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog_cat` SET `price`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  if (!empty($lRes["price"]) && file_exists($_SERVER['DOCUMENT_ROOT'].$lRes['price']))
    unlink($_SERVER['DOCUMENT_ROOT'].$lRes['price']);

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit_cat&id=".$id."'></head></html>");
}

if ($action=="delete_cover_cat_hmenu") {
	$Db->query="SELECT `horizontal_menu_background` FROM `mod_catalog_cat` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog_cat` SET `horizontal_menu_background`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['cat_hmenu'] as $v)
    if (!empty($lRes["horizontal_menu_background"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["horizontal_menu_background"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["horizontal_menu_background"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit_cat&id=".$id."'></head></html>");
}
// ===== /КАТЕГОРИИ =====

// ===== ТОВАРЫ =====
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
      $positions_arr = getEditformPositions($edit_form_fields, array('left','right','bottom', 'related_products','related_filters','additional_images','hidden'));

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
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-6">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['related_filters']).'
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-6">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['related_products']).'
              </div><!-- /.box-body -->
            </div>
          </div>
		  
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['additional_images']).'
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

// Переназначаем "пустую" категорию для товаров без категории
	if ($_POST['parent']==0) {
		$parent_id = 40;
	} else {
		$parent_id = $_POST['parent'];
	}
	$preparedData['parent'] = $parent_id;
// END Переназначаем "пустую" категорию для товаров без категории

//=== сохранение обложки товара
    if(!isset($_POST['cover_load'])) {
      if(!empty($_FILES["cover"]["name"])) {
        $source=$_FILES["cover"]["tmp_name"];
        $rand_name = rand();
        createImages($source,$rand_name,$image_props[$mod]['goods']);
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
		
/* *** СОХРАНЕНИЕ PNG КАРТИНКИ (ОБЛОЖКИ) ~COVER_PNG~ *** */
    if(!isset($_POST['cover_png_load'])) {
      if(!empty($_FILES["cover_png"]["name"])) {
        $source=$_FILES["cover_png"]["tmp_name"];
        $rand_name = rand();
        createImagestwo_png($source,$rand_name,$image_props[$mod]['goods_png']);
      }
      else {
        $rand_name = "";
      }
    }
    else {
      $rand_name = $_POST['cover_png_load'];
    }
    $preparedData['cover_png'] = $rand_name;
    unset($preparedData['cover_png_load']);
/* *** END *** СОХРАНЕНИЕ PNG КАРТИНКИ (ОБЛОЖКИ) ~COVER_PNG~ *** */

      //сохранение для related_filters
      $goods_filter = $preparedData['good_filters'];
      unset($preparedData['good_filters']);	 
	  
    //сохранение для related_products
      $related_products = $preparedData['related_products'];
      unset($preparedData['related_products']);
		
	//сохранение для related_filters_text
      $good_filters_slide = $preparedData['good_filters_slide'];
      $good_filters_hidden = $preparedData['good_filters_hidden'];
      unset($preparedData['good_filters_slide']);	 
      unset($preparedData['good_filters_hidden']);	

	//генерируем новый якорь если якоря нет
    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);

    //определяем ранг если была смена категории
    //TODO: нужны ли товарам ранги по-умолчанию?
//    if(empty($_POST["rank"]) or $_POST["parent"]!=$_POST["oldparent"]) {
//      $Db->query = "SELECT COUNT(id) FROM `$table_name` WHERE `parent`='".$_POST["parent"]."'";
//      $Db->query();
//      $lRes = mysql_fetch_assoc($Db->lQueryResult);
//      $preparedData["rank"] = $lRes['COUNT(id)']+1;
//    }

  /*  //дублируем данные для того, чтобы после создания товара отдельно соотнести все категории для него
      //сохранение для select_multiple
      $parent_multiselect = $preparedData['color']; 
      unset($preparedData["color"]);
    // /дублируем данные для того, чтобы после создания товара отдельно соотнести все категории для него*/

    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('description', 'text', 'text_two', 'anons', 'text_three', 'text_four');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);

    if ($preparedData['id']>0) $id = $preparedData['id'];
    else $id = mysql_insert_id();

    //альтернативная обработка для select_multiple
    $table_name_relations = $table_name."_brand_relations";
    $Db->query="DELETE FROM `$table_name_relations` WHERE `item`='$id'";
    $Db->query();
    if (count($parent_multiselect)>0) {
      $relations_values = '';
      foreach ($parent_multiselect as $v) {
        $relations_values .= "('$id','$v'),";
      }
      $relations_values = clearLastComma($relations_values);      
      $Db->query = "INSERT INTO `$table_name_relations` (`item`,`rel`) VALUES $relations_values";
      $Db->query();
    }

    //альтернативная обработка для additional_images
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
          createImages($_FILES[$fileElementName]['tmp_name'][$i],$myname,$image_props[$mod]['good_additional']);
          $msg.= $myname."|";
          unlink($_FILES[$fileElementName]['tmp_name'][$i]);
        }
      }
      if (!empty($msg)) {
        $file = explode("|", substr($msg,0,-1));
        $input = "";
        foreach ($file as $key=>$value) $input.= "('".$value."','".$id."'),";
        $input = substr($input,0,-1);
        $Db->query="INSERT INTO `mod_catalog_file` (`name`,`item`) VALUES ".$input;
        $Db->query();
      }	
    }

    //альтернативная обработка для related_filters
    if (count($goods_filter)>0) {
      $related_filters_table = 'mod_catalog_related_filters';
      $Db->query="DELETE FROM `$related_filters_table` WHERE `good`='$id' AND filter_slide='0'";
      $Db->query();

      $related_filters_values = '';
      foreach ($goods_filter as $v) {
        if ($v>0) $related_filters_values .= "('$id','$v'),";
      }
      
      if ($related_filters_values!='') {
        $related_filters_values = clearLastComma($related_filters_values);      
        $Db->query = "INSERT INTO `$related_filters_table` (`good`,`filter_param_id`) VALUES $related_filters_values";
        $Db->query();
      }
    }
	
	//альтернативная обработка для related_filters_text
    if (count($good_filters_slide)>0) {
      $related_filters_table = 'mod_catalog_related_filters';
      $Db->query="DELETE FROM `$related_filters_table` WHERE `good`='$id' AND filter_slide!='0'";
      $Db->query();

      $related_filters_values = '';
      foreach ($good_filters_slide as $k=>$v) {
        if ($v>0) { $related_filters_values .= "('$id','".$good_filters_hidden[$k]."','$v'),"; }
		else { $related_filters_values .= "('$id','".$good_filters_hidden[$k]."','0'),"; }
      }
      
      if ($related_filters_values!='') {
        $related_filters_values = clearLastComma($related_filters_values);      
        $Db->query = "INSERT INTO `$related_filters_table` (`good`, `filter_param_id`, `filter_slide`) VALUES $related_filters_values";
        $Db->query();
      }
    }
	
    //альтернативная обработка для $related_products
    if ($related_products!='') {
      $related_products = explode(',',$related_products);
      
      $related_products_query_values = '';
      foreach ($related_products as $v) {
        $v = mysql_query("SELECT `id` FROM `mod_catalog` WHERE `id`='$v'");
        if (mysql_num_rows($v)>0) {
          $v = mysql_fetch_assoc($v);
          $v = $v['id'];
          $related_products_query_values .= "('$id','$v'),('$v','$id'),";  
        }
      }
      $related_products_query_values = clearLastComma($related_products_query_values);
      
      $Db->query = "INSERT INTO `mod_catalog_related_products` (`item_id`,`related_to`) VALUES $related_products_query_values";
      $Db->query();
    }	
		
    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = $id;
        $name = $preparedData['name'];
        writeStat('Добавлена новый товар <a href="index.php?mod='.$mod.'&action='.$action.'&id='.$id.'" class="timeline_link_item">"'.$name.'"</a> в каталоге товаров','2');
      }
      
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
       <div class="box-body">'; 

  $Db->query="SELECT `id`, `name`, `parent`, `act` FROM `mod_catalog` ORDER BY `name` LIMIT 1";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {


//  какие поля выводить
    $fields_to_table = array('id','name','cat','conf');
//  дополнительные данные для построения таблицы
    $extra_arr = array(
      'cat' => $categories_arr,
	  'brand' => $brand_arr,
      'action_postfix' => '&page_callback='.$page
    );

  // селект с брендами
	$Db->query="SELECT `id`, `name` FROM `mod_catalog_brand` ORDER BY `name`";
	  $Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) $data_brand[] = $lRes;
		  
	}
	
	// селект с категориями
	$Db->query="SELECT * FROM `mod_catalog_cat` ORDER BY `parent`, `name`";
	  $Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		$select_filter = array();
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
		{
		  $data[$lRes['parent']][] = $lRes;
		}
		$data_cat = getTree($data, 0);
	}

    // рендерим с помощью DataTable, все данные генерируются в /admin/plugins/datatables/ajax_goods_loader.php
    $include_datatable = true;
	$datatable = "ajax_goods_loader.php";
    $content_mod .= '
        <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
          <table id="dataTable" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th></th>
                <th><h4>ID</h4></th>
                <th><h4>Название</h4></th>
                <th><h4>Категория</h4></th>
				<th><h4>Бренд</h4></th>
				<th><h4>Цена</h4></th>
				<!--<th><h4>Сопутствующие</h4></th>-->
                <th width="150" class="sorting_disabled">
					<h4><i class="fa fa-bell" aria-hidden="true" title="Хит (популярный)"></i>&nbsp;&nbsp;
					<i class="fa fa-check-square-o" title="Активность"></i>&nbsp;&nbsp;
					<i class="fa fa-trash" title="Удаление"></i></h4></th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <tr>
				<td><input type="checkbox" value="1" class="selectall" /></td>
				<td><input type="checkbox" value="" class="selectallincat" /><br />все</td>
				<td><select class="save_brand form-control" data-style="btn-default" width="150"><option value="0">Определить общий бренд</option>'.getSelectOptions($data_brand,0).'</select><a href="" class="save_brand_list pull-right btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i></a></td>
                <td colspan="2">
				<select class="save_cat form-control selectpicker" data-live-search="true"  data-style="btn-default" data-width="300px"><option value="0">Определить общую категорию</option>'.getSelectOptions($data_cat,0).'</select>
				</td>
				<td><a href="" class="save_cat_list pull-right btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i></a></td>
                <td class="text-center"><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
              </tr>
            </tfoot>
          </table>
        </form>';
	 
			$content_mod.= '<select id="list_category" style="display:none;"><option value="0">Товары без категории</option>'.getSelectOptions($data_cat,0,$sub,$tire,$self_id).'</select>';
		
  }
  else {
		$content_mod .= '<p class="have_no_item">Товаров не добавлено</p>';
  }
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>
	<div class="row">
		<div class="col-xs-12">
			<div class="box box-success">
				<div class="box-body">
					<form class="submit_filter">
						
					</form>
				</div>
			</div>
		</div>
	</div>
	';
}

// удаление обложки товара
if ($action=="delete_cover_goods") {
	$Db->query="SELECT `cover` FROM `mod_catalog` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['goods'] as $v)
    if (!empty($lRes["source"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["img_good"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["img_good"].".jpg");
  
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit&id=".$id."'></head></html>");
}

/* *** УДАЛЕНИЕ PNG КАРТИНКИ (ОБЛОЖКИ) ~COVER_PNG~ *** */
if ($action=="delete_cover_goods_png") {
  $table_name = 'mod_'.$mod;
	$Db->query="SELECT `cover_png` FROM `$table_name` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `$table_name` SET `cover_png`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['news_png'] as $v)
    if (!empty($lRes["cover"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["cover_png"].".jpg");
	
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=$id'></head></html>");
}
/* *** END *** УДАЛЕНИЕ PNG КАРТИНКИ (ОБЛОЖКИ) ~COVER_PNG~ *** */

// удаление дополнительных изображений товара
if ($action=="delete_image_good_additional") {
	$Db->query="SELECT `name`,`item` FROM `mod_catalog_file` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="DELETE FROM `mod_catalog_file` WHERE `id`='$id'";
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['good_additional'] as $v)
    if (!empty($lRes["name"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["name"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["name"].".jpg");

	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit&id=".$lRes['item']."'></head></html>");
}

// удаление связи между сопутствующими товарами
if ($action=="delete_related_product") {
  $id = explode('-',$id);
  $Db->query="DELETE FROM `mod_catalog_related_products` WHERE (`item_id`='".$id[0]."' && `related_to`='".$id[1]."') OR (`item_id`='".$id[1]."' && `related_to`='".$id[0]."')";
  $Db->query();
  
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit&id=".$id[0]."'></head></html>");
}

// ===== /ТОВАРЫ ======

// импорт прайс-листа
if ($action=="import")
	{
			if (isset($_POST['submit'])) {
				
			define('NUM_BIG_BLOCK_DEPOT_BLOCKS_POS', 0x2c);
			define('SMALL_BLOCK_DEPOT_BLOCK_POS', 0x3c);
			define('ROOT_START_BLOCK_POS', 0x30);
			define('BIG_BLOCK_SIZE', 0x200);
			define('SMALL_BLOCK_SIZE', 0x40);
			define('EXTENSION_BLOCK_POS', 0x44);
			define('NUM_EXTENSION_BLOCK_POS', 0x48);
			define('PROPERTY_STORAGE_BLOCK_SIZE', 0x80);
			define('BIG_BLOCK_DEPOT_BLOCKS_POS', 0x4c);
			define('SMALL_BLOCK_THRESHOLD', 0x1000);
			define('SIZE_OF_NAME_POS', 0x40);
			define('TYPE_POS', 0x42);
			define('START_BLOCK_POS', 0x74);
			define('SIZE_POS', 0x78);
			define('IDENTIFIER_OLE', pack("CCCCCCCC",0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1));
			define('SPREADSHEET_EXCEL_READER_BIFF8', 0x600);
			define('SPREADSHEET_EXCEL_READER_BIFF7', 0x500);
			define('SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS', 0x5);
			define('SPREADSHEET_EXCEL_READER_WORKSHEET', 0x10);
			define('SPREADSHEET_EXCEL_READER_TYPE_BOF', 0x809);
			define('SPREADSHEET_EXCEL_READER_TYPE_EOF', 0x0a);
			define('SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET', 0x85);
			define('SPREADSHEET_EXCEL_READER_TYPE_DIMENSION', 0x200);
			define('SPREADSHEET_EXCEL_READER_TYPE_ROW', 0x208);
			define('SPREADSHEET_EXCEL_READER_TYPE_DBCELL', 0xd7);
			define('SPREADSHEET_EXCEL_READER_TYPE_FILEPASS', 0x2f);
			define('SPREADSHEET_EXCEL_READER_TYPE_NOTE', 0x1c);
			define('SPREADSHEET_EXCEL_READER_TYPE_TXO', 0x1b6);
			define('SPREADSHEET_EXCEL_READER_TYPE_RK', 0x7e);
			define('SPREADSHEET_EXCEL_READER_TYPE_RK2', 0x27e);
			define('SPREADSHEET_EXCEL_READER_TYPE_MULRK', 0xbd);
			define('SPREADSHEET_EXCEL_READER_TYPE_MULBLANK', 0xbe);
			define('SPREADSHEET_EXCEL_READER_TYPE_INDEX', 0x20b);
			define('SPREADSHEET_EXCEL_READER_TYPE_SST', 0xfc);
			define('SPREADSHEET_EXCEL_READER_TYPE_EXTSST', 0xff);
			define('SPREADSHEET_EXCEL_READER_TYPE_CONTINUE', 0x3c);
			define('SPREADSHEET_EXCEL_READER_TYPE_LABEL', 0x204);
			define('SPREADSHEET_EXCEL_READER_TYPE_LABELSST', 0xfd);
			define('SPREADSHEET_EXCEL_READER_TYPE_NUMBER', 0x203);
			define('SPREADSHEET_EXCEL_READER_TYPE_NAME', 0x18);
			define('SPREADSHEET_EXCEL_READER_TYPE_ARRAY', 0x221);
			define('SPREADSHEET_EXCEL_READER_TYPE_STRING', 0x207);
			define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA', 0x406);
			define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA2', 0x6);
			define('SPREADSHEET_EXCEL_READER_TYPE_FORMAT', 0x41e);
			define('SPREADSHEET_EXCEL_READER_TYPE_XF', 0xe0);
			define('SPREADSHEET_EXCEL_READER_TYPE_BOOLERR', 0x205);
			define('SPREADSHEET_EXCEL_READER_TYPE_UNKNOWN', 0xffff);
			define('SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR', 0x22);
			define('SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS', 0xE5);
			define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS' , 25569);
			define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904', 24107);
			define('SPREADSHEET_EXCEL_READER_MSINADAY', 86400);
			define('SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT', "%s");
			function GetInt4d($data, $pos) {
				$value = ord($data[$pos]) | (ord($data[$pos+1])	<< 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
				if ($value>=4294967294) {
					$value=-2;
				}
				return $value;
			}
			class OLERead {
				var $data = '';
				function OLERead() {
					
					
				}
				function read($sFileName){
					if(!is_readable($sFileName)) {
						$this->error = 1;
						return false;
					}
					$this->data = @file_get_contents($sFileName);
					if (!$this->data) { 
						$this->error = 1; 
						return false; 
					}
					if (substr($this->data, 0, 8) != IDENTIFIER_OLE) {
						$this->error = 1; 
						return false; 
					}
					$this->numBigBlockDepotBlocks = GetInt4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
					$this->sbdStartBlock = GetInt4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
					$this->rootStartBlock = GetInt4d($this->data, ROOT_START_BLOCK_POS);
					$this->extensionBlock = GetInt4d($this->data, EXTENSION_BLOCK_POS);
					$this->numExtensionBlocks = GetInt4d($this->data, NUM_EXTENSION_BLOCK_POS);
					$bigBlockDepotBlocks = array();
					$pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
				$bbdBlocks = $this->numBigBlockDepotBlocks;
					
						if ($this->numExtensionBlocks != 0) {
							$bbdBlocks = (BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS)/4; 
						}
					
					for ($i = 0; $i < $bbdBlocks; $i++) {
						  $bigBlockDepotBlocks[$i] = GetInt4d($this->data, $pos);
						  $pos += 4;
					}
					
					
					for ($j = 0; $j < $this->numExtensionBlocks; $j++) {
						$pos = ($this->extensionBlock + 1) * BIG_BLOCK_SIZE;
						$blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, BIG_BLOCK_SIZE / 4 - 1);

						for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++) {
							$bigBlockDepotBlocks[$i] = GetInt4d($this->data, $pos);
							$pos += 4;
						}   

						$bbdBlocks += $blocksToRead;
						if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
							$this->extensionBlock = GetInt4d($this->data, $pos);
						}
					}
					$pos = 0;
					$index = 0;
					$this->bigBlockChain = array();
					
					for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++) {
						$pos = ($bigBlockDepotBlocks[$i] + 1) * BIG_BLOCK_SIZE;
						//echo "pos = $pos";	
						for ($j = 0 ; $j < BIG_BLOCK_SIZE / 4; $j++) {
							$this->bigBlockChain[$index] = GetInt4d($this->data, $pos);
							$pos += 4 ;
							$index++;
						}
					}
					$pos = 0;
					$index = 0;
					$sbdBlock = $this->sbdStartBlock;
					$this->smallBlockChain = array();
				
					while ($sbdBlock != -2) {
				
					  $pos = ($sbdBlock + 1) * BIG_BLOCK_SIZE;
				
					  for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++) {
						$this->smallBlockChain[$index] = GetInt4d($this->data, $pos);
						$pos += 4;
						$index++;
					  }
				
					  $sbdBlock = $this->bigBlockChain[$sbdBlock];
					}
					$block = $this->rootStartBlock;
					$pos = 0;
					$this->entry = $this->__readData($block);
					$this->__readPropertySets();

				}
				
				 function __readData($bl) {
					$block = $bl;
					$pos = 0;
					$data = '';
					
					while ($block != -2)  {
						$pos = ($block + 1) * BIG_BLOCK_SIZE;
						$data = $data.substr($this->data, $pos, BIG_BLOCK_SIZE);
						//echo "pos = $pos data=$data\n";	
					$block = $this->bigBlockChain[$block];
					}
					return $data;
				 }
					
				function __readPropertySets(){
					$offset = 0;
					//var_dump($this->entry);
					while ($offset < strlen($this->entry)) {
						  $d = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
						
						  $nameSize = ord($d[SIZE_OF_NAME_POS]) | (ord($d[SIZE_OF_NAME_POS+1]) << 8);
						  
						  $type = ord($d[TYPE_POS]);
						  //$maxBlock = strlen($d) / BIG_BLOCK_SIZE - 1;
					
						  $startBlock = GetInt4d($d, START_BLOCK_POS);
						  $size = GetInt4d($d, SIZE_POS);
					
						$name = '';
						for ($i = 0; $i < $nameSize ; $i++) {
						  $name .= $d[$i];
						}
						
						$name = str_replace("\x00", "", $name);
						
						$this->props[] = array (
							'name' => $name, 
							'type' => $type,
							'startBlock' => $startBlock,
							'size' => $size);

						if (($name == "Workbook") || ($name == "Book")) {
							$this->wrkbook = count($this->props) - 1;
						}

						if ($name == "Root Entry") {
							$this->rootentry = count($this->props) - 1;
						}
						$offset += PROPERTY_STORAGE_BLOCK_SIZE;
					}   
					
				}
				
				
				function getWorkBook(){
					if ($this->props[$this->wrkbook]['size'] < SMALL_BLOCK_THRESHOLD){
						$rootdata = $this->__readData($this->props[$this->rootentry]['startBlock']);
						$streamData = '';
						$block = $this->props[$this->wrkbook]['startBlock'];
						//$count = 0;
						$pos = 0;
						while ($block != -2) {
							  $pos = $block * SMALL_BLOCK_SIZE;
							  $streamData .= substr($rootdata, $pos, SMALL_BLOCK_SIZE);

							  $block = $this->smallBlockChain[$block];
						}
						
						return $streamData;
						

					}else{
					
						$numBlocks = $this->props[$this->wrkbook]['size'] / BIG_BLOCK_SIZE;
						if ($this->props[$this->wrkbook]['size'] % BIG_BLOCK_SIZE != 0) {
							$numBlocks++;
						}
						
						if ($numBlocks == 0) return '';
						$streamData = '';
						$block = $this->props[$this->wrkbook]['startBlock'];
						$pos = 0;
						while ($block != -2) {
						  $pos = ($block + 1) * BIG_BLOCK_SIZE;
						  $streamData .= substr($this->data, $pos, BIG_BLOCK_SIZE);
						  $block = $this->bigBlockChain[$block];
						}
						return $streamData;
					}
				}
				
			}
			class Spreadsheet_Excel_Reader {
				var $boundsheets = array();
				var $formatRecords = array();
				var $sst = array();
				var $sheets = array();
				var $data;
				var $_ole;
				var $_defaultEncoding;
				var $_defaultFormat = SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT;
				var $_columnsFormat = array();
				var $_rowoffset = 1;
				var $_coloffset = 1;
				var $dateFormats = array (
					0xe => "d/m/Y",
					0xf => "d-M-Y",
					0x10 => "d-M",
					0x11 => "M-Y",
					0x12 => "h:i a",
					0x13 => "h:i:s a",
					0x14 => "H:i",
					0x15 => "H:i:s",
					0x16 => "d/m/Y H:i",
					0x2d => "i:s",
					0x2e => "H:i:s",
					0x2f => "i:s.S");
				var $numberFormats = array(
					0x1 => "%1.0f",     // "0"
					0x2 => "%1.2f",     // "0.00",
					0x3 => "%1.0f",     //"#,##0",
					0x4 => "%1.2f",     //"#,##0.00",
					0x5 => "%1.0f",     /*"$#,##0;($#,##0)",*/
					0x6 => '$%1.0f',    /*"$#,##0;($#,##0)",*/
					0x7 => '$%1.2f',    //"$#,##0.00;($#,##0.00)",
					0x8 => '$%1.2f',    //"$#,##0.00;($#,##0.00)",
					0x9 => '%1.0f%%',   // "0%"
					0xa => '%1.2f%%',   // "0.00%"
					0xb => '%1.2f',     // 0.00E00",
					0x25 => '%1.0f',    // "#,##0;(#,##0)",
					0x26 => '%1.0f',    //"#,##0;(#,##0)",
					0x27 => '%1.2f',    //"#,##0.00;(#,##0.00)",
					0x28 => '%1.2f',    //"#,##0.00;(#,##0.00)",
					0x29 => '%1.0f',    //"#,##0;(#,##0)",
					0x2a => '$%1.0f',   //"$#,##0;($#,##0)",
					0x2b => '%1.2f',    //"#,##0.00;(#,##0.00)",
					0x2c => '$%1.2f',   //"$#,##0.00;($#,##0.00)",
					0x30 => '%1.0f');   //"##0.0E0";
				function Spreadsheet_Excel_Reader() {
					$this->_ole = new OLERead();
					$this->setUTFEncoder('iconv');
				}
				function setOutputEncoding($encoding) {
					$this->_defaultEncoding = $encoding;
				}
				function setUTFEncoder($encoder = 'iconv') {
					$this->_encoderFunction = '';

					if ($encoder == 'iconv') {
						$this->_encoderFunction = function_exists('iconv') ? 'iconv' : '';
					} elseif ($encoder == 'mb') {
						$this->_encoderFunction = function_exists('mb_convert_encoding') ?
												  'mb_convert_encoding' :
												  '';
					}
				}
				function setRowColOffset($iOffset) {
					$this->_rowoffset = $iOffset;
					$this->_coloffset = $iOffset;
				}
				function setDefaultFormat($sFormat) {
					$this->_defaultFormat = $sFormat;
				}
				function setColumnFormat($column, $sFormat) {
					$this->_columnsFormat[$column] = $sFormat;
				}
				function read($sFileName) {
					$res = $this->_ole->read($sFileName);
					if($res === false) {
						if($this->_ole->error == 1) {
							die('The filename ' . $sFileName . ' is not readable');
						}
					}
					$this->data = $this->_ole->getWorkBook();
					$this->_parse();
				}
				function _parse() {
					$pos = 0;

					$code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
					$length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

					$version = ord($this->data[$pos + 4]) | ord($this->data[$pos + 5])<<8;
					$substreamType = ord($this->data[$pos + 6]) | ord($this->data[$pos + 7])<<8;
					if (($version != SPREADSHEET_EXCEL_READER_BIFF8) &&
						($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
						return false;
					}

					if ($substreamType != SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS) {
						return false;
					}
					$pos += $length + 4;

					$code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
					$length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

					while ($code != SPREADSHEET_EXCEL_READER_TYPE_EOF) {
						switch ($code) {
							case SPREADSHEET_EXCEL_READER_TYPE_SST:
								 $spos = $pos + 4;
								 $limitpos = $spos + $length;
								 $uniqueStrings = $this->_GetInt4d($this->data, $spos+4);
															$spos += 8;
												   for ($i = 0; $i < $uniqueStrings; $i++) {
															if ($spos == $limitpos) {
															$opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
															$conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
																	if ($opcode != 0x3c) {
																			return -1;
																	}
															$spos += 4;
															$limitpos = $spos + $conlength;
															}
															$numChars = ord($this->data[$spos]) | (ord($this->data[$spos+1]) << 8);
															$spos += 2;
															$optionFlags = ord($this->data[$spos]);
															$spos++;
													$asciiEncoding = (($optionFlags & 0x01) == 0) ;
															$extendedString = ( ($optionFlags & 0x04) != 0);
															$richString = ( ($optionFlags & 0x08) != 0);

															if ($richString) {
																	$formattingRuns = ord($this->data[$spos]) | (ord($this->data[$spos+1]) << 8);
																	$spos += 2;
															}

															if ($extendedString) {
															  $extendedRunLength = $this->_GetInt4d($this->data, $spos);
															  $spos += 4;
															}
															$len = ($asciiEncoding)? $numChars : $numChars*2;
															if ($spos + $len < $limitpos) {
																			$retstr = substr($this->data, $spos, $len);
																			$spos += $len;
															} else {
																	$retstr = substr($this->data, $spos, $limitpos - $spos);
																	$bytesRead = $limitpos - $spos;
																	$charsLeft = $numChars - (($asciiEncoding) ? $bytesRead : ($bytesRead / 2));
																	$spos = $limitpos;

																	 while ($charsLeft > 0){
																			$opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
																			$conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
																					if ($opcode != 0x3c) {
																							return -1;
																					}
																			$spos += 4;
																			$limitpos = $spos + $conlength;
																			$option = ord($this->data[$spos]);
																			$spos += 1;
																			  if ($asciiEncoding && ($option == 0)) {
																							$len = min($charsLeft, $limitpos - $spos); // min($charsLeft, $conlength);
																				$retstr .= substr($this->data, $spos, $len);
																				$charsLeft -= $len;
																				$asciiEncoding = true;
																			  }elseif (!$asciiEncoding && ($option != 0)){
																							$len = min($charsLeft * 2, $limitpos - $spos); // min($charsLeft, $conlength);
																				$retstr .= substr($this->data, $spos, $len);
																				$charsLeft -= $len/2;
																				$asciiEncoding = false;
																			  }elseif (!$asciiEncoding && ($option == 0)) {
																							$len = min($charsLeft, $limitpos - $spos); 
																					for ($j = 0; $j < $len; $j++) {
																			 $retstr .= $this->data[$spos + $j].chr(0);
																			}
																		$charsLeft -= $len;
																			$asciiEncoding = false;
																			  }else{
																		$newstr = '';
																				for ($j = 0; $j < strlen($retstr); $j++) {
																				  $newstr = $retstr[$j].chr(0);
																				}
																				$retstr = $newstr;
																							$len = min($charsLeft * 2, $limitpos - $spos); 
																				$retstr .= substr($this->data, $spos, $len);
																				$charsLeft -= $len/2;
																				$asciiEncoding = false;
																			  }
																	  $spos += $len;

																	 }
															}
															$retstr = ($asciiEncoding) ? $retstr : $this->_encodeUTF16($retstr);

													if ($richString){
															  $spos += 4 * $formattingRuns;
															}
															if ($extendedString) {
															  $spos += $extendedRunLength;
															}
															$this->sst[]=$retstr;
												   }
								break;

							case SPREADSHEET_EXCEL_READER_TYPE_FILEPASS:
								return false;
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_NAME:
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_FORMAT:
									$indexCode = ord($this->data[$pos+4]) | ord($this->data[$pos+5]) << 8;

									if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
										$numchars = ord($this->data[$pos+6]) | ord($this->data[$pos+7]) << 8;
										if (ord($this->data[$pos+8]) == 0){
											$formatString = substr($this->data, $pos+9, $numchars);
										} else {
											$formatString = substr($this->data, $pos+9, $numchars*2);
										}
									} else {
										$numchars = ord($this->data[$pos+6]);
										$formatString = substr($this->data, $pos+7, $numchars*2);
									}

								$this->formatRecords[$indexCode] = $formatString;
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_XF:
									$indexCode = ord($this->data[$pos+6]) | ord($this->data[$pos+7]) << 8;
									if (array_key_exists($indexCode, $this->dateFormats)) {
										$this->formatRecords['xfrecords'][] = array(
												'type' => 'date',
												'format' => $this->dateFormats[$indexCode]
												);
									}elseif (array_key_exists($indexCode, $this->numberFormats)) {
										$this->formatRecords['xfrecords'][] = array(
												'type' => 'number',
												'format' => $this->numberFormats[$indexCode]
												);
									}else{
										$isdate = FALSE;
										if ($indexCode > 0){
											if (isset($this->formatRecords[$indexCode]))
												$formatstr = $this->formatRecords[$indexCode];
											if ($formatstr)
											if (preg_match("/[^hmsday\/\-:\s]/i", $formatstr) == 0) { // found day and time format
												$isdate = TRUE;
												$formatstr = str_replace('mm', 'i', $formatstr);
												$formatstr = str_replace('h', 'H', $formatstr);
											}
										}

										if ($isdate){
											$this->formatRecords['xfrecords'][] = array(
													'type' => 'date',
													'format' => $formatstr,
													);
										}else{
											$this->formatRecords['xfrecords'][] = array(
													'type' => 'other',
													'format' => '',
													'code' => $indexCode
													);
										}
									}
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR:
								$this->nineteenFour = (ord($this->data[$pos+4]) == 1);
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET:
									$rec_offset = $this->_GetInt4d($this->data, $pos+4);
									$rec_typeFlag = ord($this->data[$pos+8]);
									$rec_visibilityFlag = ord($this->data[$pos+9]);
									$rec_length = ord($this->data[$pos+10]);

									if ($version == SPREADSHEET_EXCEL_READER_BIFF8){
										$chartype =  ord($this->data[$pos+11]);
										if ($chartype == 0){
											$rec_name    = substr($this->data, $pos+12, $rec_length);
										} else {
											$rec_name    = $this->_encodeUTF16(substr($this->data, $pos+12, $rec_length*2));
										}
									}elseif ($version == SPREADSHEET_EXCEL_READER_BIFF7){
											$rec_name    = substr($this->data, $pos+11, $rec_length);
									}
								$this->boundsheets[] = array('name'=>$rec_name,
															 'offset'=>$rec_offset);

								break;

						}
						$pos += $length + 4;
						$code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
						$length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;
					}

					foreach ($this->boundsheets as $key=>$val){
						$this->sn = $key;
						$this->_parsesheet($val['offset']);
					}
					return true;

				}
				function _parsesheet($spos)
				{
					$cont = true;
					// read BOF
					$code = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
					$length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;

					$version = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5])<<8;
					$substreamType = ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8;

					if (($version != SPREADSHEET_EXCEL_READER_BIFF8) && ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
						return -1;
					}

					if ($substreamType != SPREADSHEET_EXCEL_READER_WORKSHEET){
						return -2;
					}
					$spos += $length + 4;
					while($cont) {
						$lowcode = ord($this->data[$spos]);
						if ($lowcode == SPREADSHEET_EXCEL_READER_TYPE_EOF) break;
						$code = $lowcode | ord($this->data[$spos+1])<<8;
						$length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
						$spos += 4;
						$this->sheets[$this->sn]['maxrow'] = $this->_rowoffset - 1;
						$this->sheets[$this->sn]['maxcol'] = $this->_coloffset - 1;
						unset($this->rectype);
						$this->multiplier = 1; // need for format with %
						switch ($code) {
							case SPREADSHEET_EXCEL_READER_TYPE_DIMENSION:
								if (!isset($this->numRows)) {
									if (($length == 10) ||  ($version == SPREADSHEET_EXCEL_READER_BIFF7)){
										$this->sheets[$this->sn]['numRows'] = ord($this->data[$spos+2]) | ord($this->data[$spos+3]) << 8;
										$this->sheets[$this->sn]['numCols'] = ord($this->data[$spos+6]) | ord($this->data[$spos+7]) << 8;
									} else {
										$this->sheets[$this->sn]['numRows'] = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
										$this->sheets[$this->sn]['numCols'] = ord($this->data[$spos+10]) | ord($this->data[$spos+11]) << 8;
									}
								}
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS:
								$cellRanges = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								for ($i = 0; $i < $cellRanges; $i++) {
									$fr =  ord($this->data[$spos + 8*$i + 2]) | ord($this->data[$spos + 8*$i + 3])<<8;
									$lr =  ord($this->data[$spos + 8*$i + 4]) | ord($this->data[$spos + 8*$i + 5])<<8;
									$fc =  ord($this->data[$spos + 8*$i + 6]) | ord($this->data[$spos + 8*$i + 7])<<8;
									$lc =  ord($this->data[$spos + 8*$i + 8]) | ord($this->data[$spos + 8*$i + 9])<<8;
									if ($lr - $fr > 0) {
										$this->sheets[$this->sn]['cellsInfo'][$fr+1][$fc+1]['rowspan'] = $lr - $fr + 1;
									}
									if ($lc - $fc > 0) {
										$this->sheets[$this->sn]['cellsInfo'][$fr+1][$fc+1]['colspan'] = $lc - $fc + 1;
									}
								}
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_RK:
							case SPREADSHEET_EXCEL_READER_TYPE_RK2:
								$row = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								$rknum = $this->_GetInt4d($this->data, $spos + 6);
								$numValue = $this->_GetIEEE754($rknum);
								if ($this->isDate($spos)) {
									list($string, $raw) = $this->createDate($numValue);
								}else{
									$raw = $numValue;
									if (isset($this->_columnsFormat[$column + 1])){
											$this->curformat = $this->_columnsFormat[$column + 1];
									}
									$string = sprintf($this->curformat, $numValue * $this->multiplier);
								}
								$this->addcell($row, $column, $string, $raw);
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_LABELSST:
									$row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
									$column     = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
									$xfindex    = ord($this->data[$spos+4]) | ord($this->data[$spos+5])<<8;
									$index  = $this->_GetInt4d($this->data, $spos + 6);
									$this->addcell($row, $column, $this->sst[$index]);
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_MULRK:
								$row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$colFirst   = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								$colLast    = ord($this->data[$spos + $length - 2]) | ord($this->data[$spos + $length - 1])<<8;
								$columns    = $colLast - $colFirst + 1;
								$tmppos = $spos+4;
								for ($i = 0; $i < $columns; $i++) {
									$numValue = $this->_GetIEEE754($this->_GetInt4d($this->data, $tmppos + 2));
									if ($this->isDate($tmppos-4)) {
										list($string, $raw) = $this->createDate($numValue);
									}else{
										$raw = $numValue;
										if (isset($this->_columnsFormat[$colFirst + $i + 1])){
													$this->curformat = $this->_columnsFormat[$colFirst + $i + 1];
											}
										$string = sprintf($this->curformat, $numValue * $this->multiplier);
									}
								  $tmppos += 6;
								  $this->addcell($row, $colFirst + $i, $string, $raw);
								}
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_NUMBER:
								$row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								$tmp = unpack("ddouble", substr($this->data, $spos + 6, 8));
								if ($this->isDate($spos)) {
									list($string, $raw) = $this->createDate($tmp['double']);
								}else{
									if (isset($this->_columnsFormat[$column + 1])){
											$this->curformat = $this->_columnsFormat[$column + 1];
									}
									$raw = $this->createNumber($spos);
									$string = sprintf($this->curformat, $raw * $this->multiplier);
								}
								$this->addcell($row, $column, $string, $raw);
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_FORMULA:
							case SPREADSHEET_EXCEL_READER_TYPE_FORMULA2:
								$row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								if ((ord($this->data[$spos+6])==0) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
								} elseif ((ord($this->data[$spos+6])==1) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
								} elseif ((ord($this->data[$spos+6])==2) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
								} elseif ((ord($this->data[$spos+6])==3) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
								} else {
									$tmp = unpack("ddouble", substr($this->data, $spos + 6, 8));
									if ($this->isDate($spos)) {
										list($string, $raw) = $this->createDate($tmp['double']);
									}else{
										if (isset($this->_columnsFormat[$column + 1])){
												$this->curformat = $this->_columnsFormat[$column + 1];
										}
										$raw = $this->createNumber($spos);
										$string = sprintf($this->curformat, $raw * $this->multiplier);
									}
									$this->addcell($row, $column, $string, $raw);
								}
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_BOOLERR:
								$row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								$string = ord($this->data[$spos+6]);
								$this->addcell($row, $column, $string);
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_ROW:
							case SPREADSHEET_EXCEL_READER_TYPE_DBCELL:
							case SPREADSHEET_EXCEL_READER_TYPE_MULBLANK:
								break;
							case SPREADSHEET_EXCEL_READER_TYPE_LABEL:
								$row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
								$column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
								$this->addcell($row, $column, substr($this->data, $spos + 8, ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8));
								break;

							case SPREADSHEET_EXCEL_READER_TYPE_EOF:
								$cont = false;
								break;
							default:
								break;

						}
						$spos += $length;
					}

					if (!isset($this->sheets[$this->sn]['numRows']))
						 $this->sheets[$this->sn]['numRows'] = $this->sheets[$this->sn]['maxrow'];
					if (!isset($this->sheets[$this->sn]['numCols']))
						 $this->sheets[$this->sn]['numCols'] = $this->sheets[$this->sn]['maxcol'];

				}
				function isDate($spos)
				{
					$xfindex = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
					if ($this->formatRecords['xfrecords'][$xfindex]['type'] == 'date') {
						$this->curformat = $this->formatRecords['xfrecords'][$xfindex]['format'];
						$this->rectype = 'date';
						return true;
					} else {
						if ($this->formatRecords['xfrecords'][$xfindex]['type'] == 'number') {
							$this->curformat = $this->formatRecords['xfrecords'][$xfindex]['format'];
							$this->rectype = 'number';
							if (($xfindex == 0x9) || ($xfindex == 0xa)){
								$this->multiplier = 100;
							}
						}else{
							$this->curformat = $this->_defaultFormat;
							$this->rectype = 'unknown';
						}
						return false;
					}
				}
				function createDate($numValue)
				{
					if ($numValue > 1) {
						$utcDays = $numValue - ($this->nineteenFour ? SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904 : SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS);
						$utcValue = round(($utcDays+1) * SPREADSHEET_EXCEL_READER_MSINADAY);
						$string = date ($this->curformat, $utcValue);
						$raw = $utcValue;
					} else {
						$raw = $numValue;
						$hours = floor($numValue * 24);
						$mins = floor($numValue * 24 * 60) - $hours * 60;
						$secs = floor($numValue * SPREADSHEET_EXCEL_READER_MSINADAY) - $hours * 60 * 60 - $mins * 60;
						$string = date ($this->curformat, mktime($hours, $mins, $secs));
					}

					return array($string, $raw);
				}

				function createNumber($spos)
				{
					$rknumhigh = $this->_GetInt4d($this->data, $spos + 10);
					$rknumlow = $this->_GetInt4d($this->data, $spos + 6);
					$sign = ($rknumhigh & 0x80000000) >> 31;
					$exp =  ($rknumhigh & 0x7ff00000) >> 20;
					$mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
					$mantissalow1 = ($rknumlow & 0x80000000) >> 31;
					$mantissalow2 = ($rknumlow & 0x7fffffff);
					$value = $mantissa / pow( 2 , (20- ($exp - 1023)));
					if ($mantissalow1 != 0) $value += 1 / pow (2 , (21 - ($exp - 1023)));
					$value += $mantissalow2 / pow (2 , (52 - ($exp - 1023)));

					if ($sign) {$value = -1 * $value;}
					return  $value;
				}

				function addcell($row, $col, $string, $raw = '')
				{
					$this->sheets[$this->sn]['maxrow'] = max($this->sheets[$this->sn]['maxrow'], $row + $this->_rowoffset);
					$this->sheets[$this->sn]['maxcol'] = max($this->sheets[$this->sn]['maxcol'], $col + $this->_coloffset);
					$this->sheets[$this->sn]['cells'][$row + $this->_rowoffset][$col + $this->_coloffset] = $string;
					if ($raw)
						$this->sheets[$this->sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['raw'] = $raw;
					if (isset($this->rectype))
						$this->sheets[$this->sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['type'] = $this->rectype;

				}


				function _GetIEEE754($rknum)
				{
					if (($rknum & 0x02) != 0) {
							$value = $rknum >> 2;
					} else {
					 $sign = ($rknum & 0x80000000) >> 31;
					$exp = ($rknum & 0x7ff00000) >> 20;
					$mantissa = (0x100000 | ($rknum & 0x000ffffc));
					$value = $mantissa / pow( 2 , (20- ($exp - 1023)));
					if ($sign) {$value = -1 * $value;}
					}

					if (($rknum & 0x01) != 0) {
						$value /= 100;
					}
					return $value;
				}

				function _encodeUTF16($string)
				{
					$result = $string;
					if ($this->_defaultEncoding){
						switch ($this->_encoderFunction){
							case 'iconv' :     $result = iconv('UTF-16LE', $this->_defaultEncoding, $string);
											break;
							case 'mb_convert_encoding' :     $result = mb_convert_encoding($string, $this->_defaultEncoding, 'UTF-16LE' );
											break;
						}
					}
					return $result;
				}

				function _GetInt4d($data, $pos)
				{
					$value = ord($data[$pos]) | (ord($data[$pos+1]) << 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
					if ($value>=4294967294)
					{
						$value=-2;
					}
					return $value;
				}

			}
			
			//echo $_SERVER['DOCUMENT_ROOT']; // Проверяем куда сервер временно загружает файлы
			
			$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/upload/';
			$uploadfile = $uploaddir . basename($_FILES['xlsfile']['name']);
			
			// ====Сохраняем файл для последующего использования в нашу папку====
			$uploaddir1 = $_SERVER['DOCUMENT_ROOT'].'/upload/price/';
				//Замена имени файла
			$new_name = "price.xls";
			$filename = $new_name;
			$uploadfile1 = $uploaddir1 . basename($filename);
			if (copy($_FILES['xlsfile']['tmp_name'], $uploadfile1)) {
				//echo "<h3>Файл успешно загружен на сервер</h3>";
			} else { 
				echo "<h3>Ошибка! Не удалось загрузить файл на сервер!</h3>"; exit; 
			}
			// ====END====
			
			if (move_uploaded_file($_FILES['xlsfile']['tmp_name'], $uploadfile)) {
			} else {
				echo "ERROR\n";
			}
			
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding('CP1251');
			$data->read($uploadfile);
			error_reporting(E_ALL ^ E_NOTICE);

			// массив из параметров товаров
			$goods = array();
			$Db->query="SELECT `id`,`code` FROM `mod_catalog` WHERE `code`!=''";
			$Db->query();
			if (mysql_num_rows($Db->lQueryResult)>0) while($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
				$goods[$lRes['id']]=$lRes["code"];
			
			$query_params_update = '';
			$query_params_update_2 = '';
			$query_act = '';
			$query_params_insert = '';

			for ($i = 3; $i <= $data->sheets[0]['numRows']-1; $i++) 
			{
				$code=$data->sheets[0]['cells'][$i][2];
				// пытаемся найти код текущего товара среди всех кодов товаров из таблицы с параметрами товаров
				if ($id_good=array_search($code,$goods))
				{
					//Выгрузка данных из xls
					$price= str_replace(",",".",$data->sheets[0]['cells'][$i][3]);
					$priceold= str_replace(",",".",$data->sheets[0]['cells'][$i][4]);
					$name_up= str_replace(",",".",$data->sheets[0]['cells'][$i][1]);//забирает название товара
					$name_up= iconv ('windows-1251', 'utf-8', $name_insert);
					
					$query_params_update.=" WHEN `code`='".$code."' THEN '".$price."'";
					$query_params_update_2.=" WHEN `code`='".$code."' THEN '".$priceold."'";
					$query_params_name_up.=" WHEN `code`='".$code."' THEN '".$name_up."'";
					$query_act.= " WHEN `code`='".$code."' THEN '1'";
				
				// Если в БД такого артикуля нет, то создаем новый товар
				} else {
					//Выгрузка данных из xls
					$price= str_replace(",",".",$data->sheets[0]['cells'][$i][3]);
					$priceold= str_replace(",",".",$data->sheets[0]['cells'][$i][4]);
					$code_insert= str_replace(",",".",$data->sheets[0]['cells'][$i][2]);
					$name_insert= str_replace(",",".",$data->sheets[0]['cells'][$i][1]);//забирает название товара
					$name_insert= iconv ('windows-1251', 'utf-8', $name_insert);
					//echo $name_insert;
					
					$query_params_insert="1";
					//Добавление новых данных в БД
					if (!empty($query_params_insert)) mysql_query("INSERT INTO `mod_catalog` (`name`, `code`, `price`, `priceold`) VALUES ('".$name_insert."', '".$code_insert."', '".$price."', '".$priceold."')");
				}
			}
			
			//Обновление данных в БД
			if (@$_POST["act_off"]) mysql_query("UPDATE `mod_catalog` SET `act` = '0'");
			if (!empty($query_params_update)) mysql_query("UPDATE `mod_catalog` SET `price` = CASE ".$query_params_update." ELSE `price` END");
			if (!empty($query_params_update_2)) mysql_query("UPDATE `mod_catalog` SET `priceold` = CASE ".$query_params_update_2." ELSE `priceold` END");
			if (!empty($query_params_name_up)) mysql_query("UPDATE `mod_catalog` SET `name` = CASE ".$query_params_name_up." ELSE `priceold` END");
			if (!empty($query_act)) mysql_query("UPDATE `mod_catalog` SET `act` = CASE ".$query_act." ELSE `act` END");
			
			//Добавление новых данных в БД
			//if (!empty($query_params_insert)) mysql_query("INSERT INTO `mod_catalog` (`name`, `code`, `price`, `priceold`) VALUES ('".$code_insert."', '".$code_insert."', '".$price."', '".$priceold."')");

			unlink($uploadfile);
			exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=list'></head></html>");
		} 
		else {
      $content_mod = '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                <p>Импорт корректно работает с прайс-листом в формате .xls с названием, не содержащим русских букв и пробелов. </p>
				<b>Вид прайса:</b><br/><br/><ul>
				<li>- описание товаров должно начинаться с 3 строки</li>
				<li>- на каждый товар выделяется 1 строка</li>
				<li>- артикул товара стоит в 2м столбце</li>
				<li>- цена товара стоит в 3м</li>
				<li>- оптовая цена товара стоит в 4м</li>
				<li>- если товар, присутствующий на сайте, отсутствует в прайс-листе - с него снимается активность на сайте</li>
				<li>- Последней строкой не должен идти товар. После последнего товара пропишите в следующей строке: КОНЕЦ ПРАЙС-ЛИСТА</li>
				<li>- <a href="/upload/adm/price.xls">Скачать пример прайс-листа</a></li>
				<br />
                <input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
                <div class="form-group">
                  <label class="label-control" for="xls_file">Файл XLS</label>
                  <input type="file" id="xls_file" name="xlsfile" />
                </div>
                
                
                <div class="form-group">
                  <label for="act_off">
                    <input type="checkbox" id="act_off" name="act_off" value="on" checked> Снять активность со всех товаров перед обновлением
                  </label>
                </div>
              </div><!-- /.box-body -->

              <div class="box-footer">
                <input type="submit" class="btn btn-danger pull-right" value="Загрузить" name="submit" />
              </div>
            </div>
          </div>
        </form>
      </div>';
		}	
	}	

///////////////////////////////////////// БРЕНДЫ /////////////////////////////////////////////////
$table_name = 'mod_catalog_brand';
// вывод списка основных элементов
if ($action=="list_brand") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT * FROM `$table_name` ORDER BY `name` ASC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('id','name','conf');
    $extra_array = array('action_postfix'=>'_brand');

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet_brand" name="form1">
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
		$content_mod .= '<p class="have_no_item">Брендов не добавлено</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div>';
}

// редактирование
if ($action=="edit_brand") {
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
        createImagestwo_png($source,$rand_name,$image_props[$mod]['brand']);
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
	
    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);

    //$preparedData['edit_date'] = 'NOW()';
    //$preparedData['edit_id'] = $global_user;

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('text','anons');
    $saveStatus = saveEditForm($Db,$table_name,$preparedData,$filterExceptions);
    
    if ($saveStatus==true) {
      if ($preparedData['id']<1) {
        $new_id = mysql_insert_id();
        $name = $preparedData['name'];
        writeStat('Добавлен новый цвет <a href="index.php?mod='.$mod.'&action=edit_brand&id='.$new_id.'" class="timeline_link_item">"'.$name.'"</a>','2');
      }
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list_brand'></head></html>");
    }
  }
}	
	
// удаление обложки товара
if ($action=="delete_cover_brand") {
	$Db->query="SELECT `cover` FROM `mod_catalog_brand` WHERE `id`='$id'";
	$Db->query();
	$lRes=mysql_fetch_assoc($Db->lQueryResult);
	$Db->query="UPDATE `mod_catalog_brand` SET `cover`='' WHERE `id` = '$id'"; 
	$Db->query();

  //получаем из настроек префикс и название папки изображений текущего модуля и типа элементов модуля
  //и удаляем файл, если находим
  foreach ($image_props[$mod]['brand'] as $v)
    if (!empty($lRes["source"]) && file_exists($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["img_good"].".jpg"))
      unlink($_SERVER['DOCUMENT_ROOT']."/upload/".$v['folder']."/".$v['prefix'].$lRes["img_good"].".jpg");
  
	exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=catalog&action=edit_brand&id=".$id."'></head></html>");
}


/* **************************************** */
/* *** ПОДМОДУЛЬ "ОТЗЫВЫ И КОММЕНТАРИИ" *** */
/* **************************************** */
if ($action=="edit_comment") {
  $table_name = 'mod_'.$mod.'_comment';
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

    //генерируем новый якорь если якоря нет
    if (empty($preparedData["anchor"])) 
      $preparedData["anchor"] = trans($preparedData["name"]);
    
		$preparedData['edit_date'] = 'NOW()';
    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $filterExceptions = array('text', 'text_two');
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData,$filterExceptions);

    if ($saveStatus==true) { 
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list_comment'></head></html>");
    } 
	}
}

if ($action=="list_comment") {
  $table_name = 'mod_'.$mod.'_comment';
  $content_mod = '
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-danger">
          <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT * FROM `$table_name` ORDER BY `date` DESC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[$lRes['parent']][] = $lRes;
    $data = getTree($data, 0);

    //какие поля выводить
    $fields_to_table = array('id','name','text','date_only','conf');
    $extra_array = array('action_postfix'=>'_comment');

    $content_mod .= '
            <form method="post" action="index.php?mod='.$mod.'&action=pereschet_comment" name="form1">
              <table class="table table-hover">
                <tr>
                  <th width="2%"><h4>ID</h4></th>
                  <th><h4>Имя</h4></th>
				  <th><h4>Отзыв</h4></th>
				  <th><h4>Дата</h4></th>
                  <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
                </tr>
                '.getTreeTable($data, '', $mod, $fields_to_table, $extra_array).'
                <tr>
                  <td colspan="4"></td>
                  <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
                </tr>
              </table>
            </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Отзывов нет</p>';
  }
  
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>';
}	
/* ****************** END ***************** */
/* *** ПОДМОДУЛЬ "ОТЗЫВЫ И КОММЕНТАРИИ" *** */
/* **************************************** */
	