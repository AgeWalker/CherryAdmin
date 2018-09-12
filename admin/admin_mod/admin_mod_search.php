<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
// вывод списка основных элементов
if ($action=="list") {
  $content_mod = '
	<div class="row">
		<div class="col-xs-12">
		  <div class="box box-danger">
			<div class="box-body table-responsive no-padding">';

$Db->query = "SELECT * FROM `mod_search` ORDER BY `id` DESC LIMIT 50";
$Db->query();
if (mysql_num_rows($Db->lQueryResult)>0) {
  $content_mod .= '
        <form method="post" action="index.php?mod='.$mod.'&action=pereschet" name="form1">
          <table class="table table-hover">
          <tr>
            <th width="2%"><h4>ID</h4></th>
            <th><h4>Запрос</h4></th>
            <th><h4>Дата</h4></th>
          </tr>';
  while($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
    $content_mod .= '
          <tr>
            <td>'.$lRes['id'].'</td>
            <td>'.$lRes['text'].'</td>
            <td>'.formatedDate($lRes['date']).'</td>
          </tr>';
  }
  $content_mod .= '
          </table>
        </form>';
} else {
  $content_mod .= '<p class="have_no_item">Поисковых запросов нет</p>';
}
  
  $content_mod .= '
			  </div>
		  </div>
	  </div>
  </div>';
}

if ($action=='truncate_search') {
  echo '<p>Обработка информации, пожалуйста подождите...</p>';
  $Db->query="TRUNCATE TABLE `mod_search`";
  $Db->query();
  exit("<html><head><meta  http-equiv='Refresh' content='0; URL=index.php?mod=$mod&action=list'></head></html>");
}