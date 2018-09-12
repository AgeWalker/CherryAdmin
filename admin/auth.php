<? // глобальная авторизация
if  (empty($_SESSION['ca_user']) or empty($_SESSION['ca_hash']) or empty($_SESSION['ca_access'])) {
  $autorized = 0;
	unset($_SESSION['ca_user']);
	unset($_SESSION['ca_hash']);
	unset($_SESSION['ca_access']);
  unset($_SESSION['KCFINDER']);
}
else {
  $filter = new filter;
  $global_user = $filter->html_filter($_SESSION['ca_user']);
  $hash = $filter->html_filter($_SESSION['ca_hash']);
  $access = $filter->html_filter($_SESSION['ca_access']);

  $Db->query="SELECT `hash`,`username`,`date` FROM `mod_admin` WHERE `id`='$global_user' LIMIT 1";
  $Db->query();
  $lRes=mysql_fetch_assoc($Db->lQueryResult);
	if ($lRes["hash"]!=$hash) {
		$autorized = 0;
		unset($_SESSION['ca_user']);
		unset($_SESSION['ca_hash']);
		unset($_SESSION['ca_access']);
		unset($_SESSION['KCFINDER']);
	}
	else {
		$autorized = 1; 
		$status = $lRes["username"]; 
		$mydate = formatedDate($lRes["date"]);

		//включение файлового менеджера для админа
		$_SESSION['KCFINDER'] = array();
		$_SESSION['KCFINDER']['disabled'] = false;
	}
}