<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// понять выше rank
if (substr_count($action,'rankup')>0) {
  $postfix = str_replace('rankup','',$action);
  $table_name = 'mod_'.$mod.$postfix;
  
  $up = $rank - 1;
  $Db->query="UPDATE `$table_name` SET `rank`='$up' WHERE `id`='$id'";
  $Db->query();
  $Db->query="SELECT `id`,`rank` FROM `$table_name` WHERE `id`!='$id' AND `parent`='$cat'";
  $Db->query();
  while($lRes=mysql_fetch_assoc($Db->lQueryResult)) $queryrank[$lRes['id']] = $lRes['rank'];
    foreach ($queryrank as $key => $val) {
    if ($up==$val) { 
      $val++; 
      mysql_query("UPDATE `$table_name` SET `rank`='$val' WHERE `id`='$key'");
    }
  }
  
  $callback_action = 'list'.$postfix;
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=$callback_action'></head></html>");
}

//опустить ниже rank
if (substr_count($action,'rankdown')>0) {
  $postfix = str_replace('rankdown','',$action);
  $table_name = 'mod_'.$mod.$postfix;
  
  $down = $rank + 1;
  $Db->query="UPDATE `$table_name` SET `rank` = '$down' WHERE `id` = '$id'";
  $Db->query();
  $Db->query="SELECT `id`,`rank` FROM `$table_name` WHERE `id`!='$id' AND `parent`='$cat'";
  $Db->query();
  while($lRes=mysql_fetch_assoc($Db->lQueryResult)) $queryrank[$lRes['id']] = $lRes['rank'];
    foreach ($queryrank as $key => $val) {
    if ($down==$val) { 
      $val--; 
      mysql_query("UPDATE `$table_name` SET `rank` = '$val' WHERE `id`='$key'");
    }
  }
  
  $callback_action = 'list'.$postfix;
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=$callback_action'></head></html>");
}

//пересчёт с удалением либо с изменением активности
//TODO: добавить удаление изображений при удалении товара/категории/тд
if (substr_count($action,'pereschet')>0) {
  $postfix = str_replace('pereschet','',$action);
  $table_name = 'mod_'.$mod.$postfix;
  
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
  
	if(!empty($_POST["act_two"])) {
    $case_query = '';
    foreach($_POST["act_two"] as $key=>$val) {
      $case_query .= " WHEN `id`='$key' THEN '$val'";
    }
    $Db->query="UPDATE `$table_name` SET `act_two` = CASE $case_query ELSE `act_two` END";
    $Db->query();
  }
  
  
	if(!empty($_POST["act_three"])) {
    $case_query = '';
    foreach($_POST["act_three"] as $key=>$val) {
      $case_query .= " WHEN `id`='$key' THEN '$val'";
    }
    $Db->query="UPDATE `$table_name` SET `act_three` = CASE $case_query ELSE `act_three` END";
    $Db->query();
  }

  $callback_action = 'list'.$postfix;
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=$callback_action'></head></html>");
}

//TODO: написать обобщённую функцию и для удаления изображений

//настройки в модуле
//TODO: привести в нормальный вид
if ($action=="config") {
  if (!@$_POST["save"]) {
    $Db->query="SELECT * FROM `mod_config` WHERE `mod`='$mod'";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) {
      $content_mod = '
      <div class="row">
        <form role="form" method="post">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">';
      $form_input_counter = 1;
      while($lRes=mysql_fetch_assoc($Db->lQueryResult)) {
        $content_mod .= '
                <div class="form-group">
                  <label class="label-control" for="form_inp_'.$form_input_counter.'">'.$lRes['name'].'</label>';
        if($lRes['type']=="checkbox") 
          $content_mod.= '<input type="hidden" name="'.$lRes['option'].'" value="0">';

        if($lRes['type']=="checkbox" && $lRes['value']=="on")
          $chek = ' checked="checked"';
        else 
          $chek = '';

        $content_mod.= '<input class="form-control" id="form_inp_'.$form_input_counter.'" name="'.$lRes['option'].'" type="'.$lRes['type'].'"'.$chek.' value="'.$lRes['value'].'" />';
        
        $content_mod .= '
                </div>';
        $form_input_counter++;
      }
        $content_mod .= '
            </div><!-- /.box-body -->

            <div class="box-footer">
              <input type="submit" class="btn btn-danger pull-right" value="Сохранить" name="save" />
            </div>
          </div>
        </div>
      </form>
    </div>';
    }
    else {
      $content_mod.= '<p>Настройки для данного модуля не найдены.</p>';
    }
  }
  else {
    // обрабатываем форму сохранения настроек
    echo '<p>Обработка информации, пожалуйста подождите...</p>';
    unset($_POST['save']);
    $query = '';
    foreach ($_POST as $key=>$value) $query.= " WHEN `option`='".$key."' THEN '".$value."'";	
    $Db->query="UPDATE `mod_config` 
    SET `value` = CASE ".$query."
    ELSE `value` END";
    $Db->query(); 
    exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=".$mod."&action=config'></head></html>");
  }
}

// параметры изображений
if ($action=="imageconfig") {
	if (!@$_POST["save"]) {
		$Db->query="SELECT * FROM `images_properties` WHERE `module`='".$mod."'";
		$Db->query();
		if (mysql_num_rows($Db->lQueryResult)>0) {
      $content_mod = '
      <div class="row">
        <form role="form" method="post">';
      
      while($lRes=mysql_fetch_assoc($Db->lQueryResult)) {
        $content_mod .= '
        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="box box-danger">
            <div class="box-body">';

        $content_mod .= '<h4>'.$lRes['name'].'</h4>';

        $content_mod .= '
        <div class="form-group">
          <label class="label-control" for="width['.$lRes['id'].']">Ширина:</label>
          <input class="form-control" type="text" id="width['.$lRes['id'].']" name="width['.$lRes['id'].']" value="'.$lRes['width'].'" />
        </div>';

        $content_mod .= '
        <div class="form-group">
          <label class="label-control" for="height['.$lRes['id'].']">Высота:</label>
          <input class="form-control" type="text" id="height['.$lRes['id'].']" name="height['.$lRes['id'].']" value="'.$lRes['height'].'" />
        </div>';

        $content_mod .= '
        <div class="form-group">
          <label class="label-control" for="folder['.$lRes['id'].']">Папка:</label>
          <input class="form-control" type="text" id="folder['.$lRes['id'].']" name="folder['.$lRes['id'].']" value="'.$lRes['folder'].'" />
        </div>';

        $content_mod .= '
        <div class="form-group">
          <label class="label-control" for="prefix['.$lRes['id'].']">Префикс:</label>
          <input class="form-control" type="text" id="prefix['.$lRes['id'].']" name="prefix['.$lRes['id'].']" value="'.$lRes['prefix'].'" />
        </div>';

        $imgResize_func = '';
        $create_tumbnails_func = '';
        $move_upl_file_func = '';
        if ($lRes['function']==1) $imgResize_func = ' checked ';
        elseif ($lRes['function']==2) $create_tumbnails_func = ' checked ';
        elseif ($lRes['function']==3) $move_upl_file_func = ' checked ';
        $content_mod .= '
        <label class="label-control">Тип обработки</label>
        <div class="radio">
          <label>
            <input type="radio" id="function1['.$lRes['id'].']" name="function['.$lRes['id'].']" value="1" '.$imgResize_func.'/>
            Дополнение фоновым цветом (imgResize())
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" id="function2['.$lRes['id'].']" name="function['.$lRes['id'].']" value="2" '.$create_tumbnails_func.'/>
            Изменение исходного изображения (create_tumbnail())
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" id="function3['.$lRes['id'].']" name="function['.$lRes['id'].']" value="3" '.$move_upl_file_func.'/>
            Просто скопировать загружаемое изображение с сохранением расширения
          </label>
        </div>';

        $do_cut_checked = ($lRes['do_cut']==1) ? ' checked ' : '';
        $content_mod .= '
        <div class="form-group">
          <input type="hidden" name="do_cut['.$lRes['id'].']" value="0" />
          <label>
            <input type="checkbox" name="do_cut['.$lRes['id'].']" value="1" '.$do_cut_checked.'/>
            Обрезать изображение (для create_tumbnail())
          </label>
        </div>';
        
        $content_mod .= '
            </div><!-- /.box-body -->
          </div>
        </div>';
      }
      $content_mod .= '
          <div class="col-md-12">
            <input type="submit" class="btn btn-danger pull-right" value="Сохранить" name="save" />
          </div>
        </form>
      </div>';
		}
		else {
			$content_mod.= '<p>Настройки изображений для данного модуля не найдены.</p>';
		}
	}
	else
	{
		// обрабатываем форму сохранения настроек
		unset($_POST['save']);
    echo '<p>Обработка информации, пожалуйста подождите...</p>';

		foreach ($_POST as $key=>$value) {
      $query = '';
      foreach ($value as $k=>$v) {
        $query.= " WHEN `id`='$k' THEN '$v'";	
        $Db->query="UPDATE `images_properties` 
        SET `$key` = CASE ".$query."
        ELSE `$key` END";
      }
//      var_dump($Db->query);
      $Db->query();
    }
    exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=".$mod."&action=imageconfig'></head></html>");
	}
}