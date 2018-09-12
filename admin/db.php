<?
define( "DOCUMENT_ROOT", $_SERVER['DOCUMENT_ROOT'] );
$SITE_ROOT = $_SERVER["DOCUMENT_ROOT"];
$UPLOAD_ROOT = "/upload"; // Папка загрузок файлов и картинок
$DomenName = "http://".$_SERVER['SERVER_NAME'].'/';
$SiteName = 'Название сайта'; //теперь будет использоваться везде в e-mail оповещениях с сайта
$MailRobot = '"'.$SiteName.'" <robot@'.$_SERVER['SERVER_NAME'].'>'; //e-mail адрес робота

//хак для разных данных для бд на локальном сервере и хостинге
if ($_SERVER['REMOTE_ADDR']=='127.0.0.1') {
	$DBServer = "localhost";
	$DBLogin = "root";
	$DBPassword = "";
	$DBName = "";
	$DBPrefix = "";
} else {
	$DBServer = ""; // Сервер БД
	$DBLogin = ""; // Логин БД
	$DBPassword = ""; // Пароль БД
	$DBName = ""; // Название БД
	$DBPrefix = "";
}
?>
