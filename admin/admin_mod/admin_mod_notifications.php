<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
$status_arr = array(
  0=>'не обработано',
  1=>'обработано',
  2=>'переговоры',
  3=>'сделка'
);

// вывод списка основных элементов
if ($action=="list") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
        <div class="box-body table-responsive no-padding">';

  $Db->query="SELECT `id`,`name`,`date`,`status` FROM `$table_name` ORDER BY `date` DESC";
  $Db->query();
  if (mysql_num_rows($Db->lQueryResult)>0) {
    while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
      $data[0][] = $lRes;
    $data = getTree($data, 0);

    //  какие поля выводить
    $fields_to_table = array('id','name','date_only','status','delete');
    $extra = array(
      'status_arr'=>$status_arr,
      'alter_action'=>'show'
    );

    $content_mod .= '
          <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
            <table class="table table-hover">
            <tr>
              <th width="2%"><h4>ID</h4></th>
              <th><h4>Название</h4></th>
              <th><h4>Дата получения</h4></th>
              <th><h4>Статус</h4></th>
              <th width="10%"><h4>&nbsp;<i class="fa fa-trash"></i></h4></th>
            </tr>
            '.getTreeTable($data, '', $mod, $fields_to_table, $extra).'
            <tr>
              <td colspan="4"></td>
              <td><button type="submit" class="btn btn-danger" /><i class="fa fa-refresh"></i></button></td>
            </tr>
            </table>
          </form>';
  }
  else {
		$content_mod .= '<p class="have_no_item">Оповещений нет</p>';
  }
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div>';
}

// редактирование
if ($action=="show") {
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
                  <label class="label-control">Название</label>
                  <p>'.$edit_parameters['name'].'</p>
                </div>

                <div class="form-group">
                  <label class="label-control">Дата получения</label>
                  <p>'.formatedDate($edit_parameters['date']).'</p>
                </div>

                <div class="form-group">
                  <label class="label-control">Текст оповещения</label>
                  <p>'.$edit_parameters['text'].'</p>
                </div>

                <div class="form-group">
                  <label for="status">Статус</label>
                  <select id="status" name="status" class="form-control">';
      foreach ($status_arr as $k=>$v) {
        $this_selected = ($edit_parameters['status']==$k) ? 'selected' : '';
        $content_mod .= '<option value="'.$k.'" '.$this_selected.'>'.$v.'</option>';
      }
      $content_mod .= '
                  </select>
                </div>
                
              </div><!-- /.box-body -->
              <div class="box-footer">
                <input type="hidden" name="id" value="'.$id.'" />
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

    //до вызова этой функции $preparedData уже должен быть очищен от лишних переменных
    //$filterExceptions - какие поля обрабатывать не через $filter, а через mysql_escape_string
    $saveStatus = saveEditForm($Db,$table_name,$preparedData);
    
    if ($saveStatus==true) {
      exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
    }
  }
}