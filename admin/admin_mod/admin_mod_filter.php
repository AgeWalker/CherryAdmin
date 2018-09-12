<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// ===== Фильтры =====
if ($action=="edit") {
  $table_name = 'mod_'.$mod;
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

      //сортируем все поля по их позициям
      $positions_arr = getEditformPositions($edit_form_fields, array('main','params','hidden'));

      //генерируем внешний вид формы редактирования
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['main']).'
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['params']).'
              </div><!-- /.box-body -->
            </div>
          </div>

          <div class="col-md-12">
            <div class="box box-danger">
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
	 $parent_multiselect = $preparedData['parent']; 
    unset($preparedData["parent"]);
	
    //до вызова этой функции $_POST уже должен быть очищен от лишних функций
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData);

	
	if ($preparedData['id']>0) $id = $preparedData['id'];
    else $id = mysql_insert_id();
	
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
      /* TODO: узнать на счёт логирования
      if (empty($_POST["id"]) {
        writeStat('Новая категория',$preparedData["name"]);
      } else {
        writeStat('Отредактирована категория',$preparedData["name"]);
      }
      */

      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
    } 
	}
}

if ($action=="list") {
  $table_name = 'mod_'.$mod.'';
  $content_mod .= '
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-danger">
          <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT `id`, `name`, `act` FROM `$table_name` ORDER BY `id`";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //какие поля выводить
    $fields_to_table = array('id','name','conf');

    $content_mod .= '
            <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
              <table class="table table-hover">
                <tr>
                  <th width="2%"><h4>ID</h4></th>
                  <th><h4>Название</h4></th>
                  <th width="10%"><h4>&nbsp;<i class="fa fa-check-square-o"></i>&nbsp;&nbsp;<i class="fa fa-trash"></i></h4></th>
                </tr>
                '.getTreeTable($data, '', $mod, $fields_to_table).'
                <tr>
                  <td colspan="2"></td>
                  <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
                </tr>
              </table>
            </form>';
  }
  else
		$content_mod = "Параметров нет";
  
  $content_mod .= '
          </div>
        </div>
      </div>
    </div>';
}
// ===== /Фильтры =====

// ===== Параметры фильтров =====
if ($action=="edit_params") {
  $table_name = 'mod_'.$mod.'_params';
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
          $lRes = array(
            'Field' => $lRes['Field'],
            'Comment' => $lRes['Comment']
          );
          $edit_form_fields[$lRes['Field']] = getEditformFieldView($lRes,$edit_parameters,$Db,$mod);
        }
      }

      //сортируем все поля по их позициям
      $positions_arr = getEditformPositions($edit_form_fields, array('main','hidden'));

      //генерируем внешний вид формы редактирования
      $content_mod .= '
      <div class="row">
        <form role="form" method="post" enctype="multipart/form-data">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">
                '.implode($positions_arr['main']).'
              </div><!-- /.box-body -->
              <div class="box-footer">
                '.implode($positions_arr['hidden']).'
                <input type="hidden" name="parent" value="'.$parent.'" />
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
    $saveStatus = saveEditForm($Db,"$table_name",$preparedData);

    if ($saveStatus==true) {
      /* TODO: узнать на счёт логирования
      if (empty($_POST["id"]) {
        writeStat('Новая категория',$preparedData["name"]);
      } else {
        writeStat('Отредактирована категория',$preparedData["name"]);
      }
      */
      
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=".$preparedData['parent']."'></head></html>");
    } 
	}
}

if ($action=='delete_filter_params') {
  $Db->query="SELECT `parent` FROM `mod_filter_params` WHERE `id`='$id'";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    $parent = mysql_fetch_assoc($Db->lQueryResult);
    $parent = $parent['parent'];
    
    $Db->query="DELETE FROM `mod_filter_params` WHERE `id`='$id'";
    $Db->query();
    
    exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=edit&id=$parent'></head></html>");    
  } else {
    echo '<p>Ошибка! Параметр не существует</p>';
  }
}
// ===== /Параметры фильтров =====