<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");

$config_actions = array('common','seo','scripts','confirms');
if (in_array($action,$config_actions)) {
  if (!@$_POST["save"]) {
    $Db->query="SELECT * FROM `mod_config` WHERE `mod`='$action' ORDER BY `id_config`";
    $Db->query();
    if (mysql_num_rows($Db->lQueryResult)>0) {
      $content_mod = '
      <div class="row">
        <form role="form" method="post">

          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-body">';
      while($lRes=mysql_fetch_assoc($Db->lQueryResult)) {        
        switch ($lRes['type']) {
          case 'checkbox':
            $thisChecked = ($lRes['value']=='on') ? 'checked' : '';
            $content_mod .= '                  
                <div class="form-group">
                  <label for="form_'.$lRes['option'].'">
                    <input type="hidden" name="'.$lRes['option'].'" value="0">
                    <input type="checkbox" id="form_'.$lRes['option'].'" name="'.$lRes['option'].'" value="1" '.$thisChecked.'> '.$lRes['name'].' />
                  </label>';
            break;
            
          case 'text':
            $content_mod .= '
                <div class="form-group">
                  <label for="form_'.$lRes['option'].'">'.$lRes['name'].'</label>
                  <input type="text" class="form-control" id="form_'.$lRes['option'].'" name="'.$lRes['option'].'" value="'.$lRes['value'].'" />';
            break;
            
          case 'code':
            $content_mod .= '
                <div class="form-group">
                  <label for="form_'.$lRes['option'].'">'.$lRes['name'].'</label>
                  <textarea class="form-control" id="form_'.$lRes['option'].'" name="'.$lRes['option'].'" rows="10" cols="80">'.$lRes['value'].'</textarea>';
            break;
        }

        if ($lRes['help_text']!='') $content_mod .= '
                  <span class="help-block">'.$lRes['help_text'].'</span>';
        $content_mod .= '
                </div>';
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
    foreach ($_POST as $key=>$value) {
		if (($key=='yandex_verification')||($key=='google_verification')) $value = str_replace('"',"'",$value); else $value =  mysql_escape_string($value);
		//$query.= " WHEN `option`='$key' THEN '".$value."'";
		$query.= ' WHEN `option`="'.$key.'" THEN "'.$value.'"';
		
	}
    $Db->query="UPDATE `mod_config` SET `value` = CASE $query ELSE `value` END";
    $Db->query(); 
    exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=".$mod."&action=$action'></head></html>");
  }
}