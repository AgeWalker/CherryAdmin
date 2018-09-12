<?php
session_start();

// подключаем основные функции и классы
require_once($_SERVER['DOCUMENT_ROOT']."/core/functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/admin/db.php");

$Db=new Db ($DBServer,$DBLogin,$DBPassword,$DBName);
$Db->connect();
$Db->query = "SET NAMES 'utf8'";
$Db->query();

$filter = new filter();

$action = $filter->html_filter($_POST['action']);
$out = array();
$out['status'] = 0; //ошибка

if ($action=='save') {
	$mod = $filter->html_filter($_POST["mod"]); 
	$value = $filter->html_filter($_POST["value"]); 
	$colum = $filter->html_filter($_POST["colum"]); 
	$id = $filter->html_filter($_POST["id"]); 
	if ($id>0)
	{
		$Db->query="UPDATE `mod_".$mod."` SET `".$colum."` = '".$value."' WHERE `id`='".$id."' LIMIT 1";
		$Db->query();
	}
}


if ($action=='add_goods_file') {

	if (count($_POST['files'])>0 && count($_POST['goods'])>0)
		{
			$insert = '';
			foreach ($_POST['files'] as $k=>$v)
			{
				$ras = getExtension1($_POST['files_name'][$k]);

				foreach ($_POST['goods'] as $vv) $insert.= "('".$_POST['files_name'][$k]."', '".$vv."' , '".$v."', '".$ras."'),";	
				
			}
			$Db->query="INSERT INTO `mod_catalog_sert` (`name`, `item`, `title`, `ras`) VALUES ".substr($insert, 0, -1); 
			$Db->query();
			
		}

}

if ($action=='del_goods_file') {
	mysql_query("DELETE FROM mod_catalog_sert WHERE `item` IN (".implode(',', $_POST['goods']).")");
}


if ($action=='add_filter') {
	
	//mysql_query("DELETE FROM mod_catalog_related_filters WHERE `good` IN (".implode(',', $_POST['goods']).")");
	$insert = '';
	foreach($_POST['filter'] as $v)
	{
		foreach ($_POST['goods'] as $vv) $insert.= '('.$vv.', '.$v.'),';	
	}
	$insert = substr($insert, 0, -1);
	mysql_query("INSERT INTO mod_catalog_related_filters (`good`, `filter_param_id`) VALUES ".$insert."");
}


if ($action=='reset_filter') {
	mysql_query("DELETE FROM mod_catalog_related_filters WHERE `good` IN (".implode(',', $_POST['goods']).")");
}


if ($action=='show_filter') {
	$id = (int)$_POST['id'];
	if ($id>0)
	{
		$query = mysql_query("SELECT `id`,`name` FROM `mod_filter` WHERE `catalog_cat`='".$id."' ORDER BY name");
        if (($query)&&(mysql_num_rows($query)>0)) {
          //фильтры
          $filters_arr = array();
          while($lRes = mysql_fetch_assoc($query)) {
            $filters_arr[$lRes['id']] = $lRes['name'];
          }
          
          //параметры фильтров
          $query = mysql_query("SELECT `id`,`name`,`parent` FROM `mod_filter_params`");
          if (($query)&&(mysql_num_rows($query)>0)) {
            //параметры
            $params_arr = array();
            while($lRes = mysql_fetch_assoc($query)) {
              $params_arr[$lRes['parent']][$lRes['id']] = $lRes['name'];
            }
          }

          $list = '';
          foreach ($filters_arr as $filter=>$filter_name) {
            $list .= '
            <div class="form-group form-inline filter_params_select">
              <label class="label-control" for="good_filters_'.$filter.'">'.$filter_name.':</label>
              <select class="form-control" name="good_filters['.$filter.']" id="good_filters_'.$filter.'">
                <option value="0">не привязан</option>';
            foreach ($params_arr[$filter] as $param=>$param_name) {
              $list .= '
                <option value="'.$param.'" '.$selected_param.'>'.$param_name.'</option>';
            }
            $list .= '
              </select>
            </div>';
          }
        } 
		$out["items"] = $list;
	}
}


if ($action=='del_cat') {
	$id = (int)$_POST['thisid'];
	if ($id>0)
	{
		$Db->query="DELETE FROM `mod_catalog_cat` WHERE `id`='".$id."' LIMIT 1";
		$Db->query();
	}
}


if ($action=='save_cat_act') {

	$id = (int)$_POST['thisid'];
	$act = (int)$_POST['thisact'];

	if ($id>0)
	{
		$Db->query="UPDATE `mod_catalog_cat` SET act='".$act."' WHERE `id`='".$id."' LIMIT 1";
		$Db->query();
	}
}

if ($action=='save_cat_forall') {
	$id = (int)$_POST['thisselect'];
	if (count($_POST['id'])>0)
	{
		foreach ($_POST['id'] as $v)
		{
			$Db->query="UPDATE ".$DBPrefix."mod_catalog SET `parent`='$id' WHERE `id`='".(int)$v."' LIMIT 1";
			$Db->query();
		}
	}
}

if ($action=='save_cat_forall_all') {
	$select = (int)$_POST['thisselect'];
	$id = (int)$_POST['id'];
	
	$Db->query="UPDATE ".$DBPrefix."mod_catalog SET `parent`='$select' WHERE `parent`='".$id."'";
	$Db->query();
}
if ($action=='save_brand_forall_all') {

	$select = (int)$_POST['thisselect'];
	$id = (int)$_POST['id'];
	
	$Db->query="UPDATE ".$DBPrefix."mod_catalog SET `brand`='$select' WHERE `parent`='".$id."'";
	$Db->query();

}

if ($action=='save_brand_forall') {
	$id = (int)$_POST['thisselect'];
	if (count($_POST['id'])>0)
	{
		foreach ($_POST['id'] as $v)
		{
			$Db->query="UPDATE ".$DBPrefix."mod_catalog SET `brand`='$id' WHERE `id`='".(int)$v."' LIMIT 1";
			$Db->query();
		}
	}
}

if ($action=='del_goods') {
	$id = (int)$_POST['id'];
	$rel = (int)$_POST['rel'];
	mysql_query("DELETE FROM mod_catalog_related_products WHERE `item_id`='".$id."' AND `related_to`='".$rel."' LIMIT 1");

	$Db->query="SELECT `name` FROM `mod_catalog`
				LEFT JOIN `mod_catalog_related_products` ON (mod_catalog_related_products.related_to = mod_catalog.id)
				WHERE `item_id`='".$id."'";
	$Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) $items.= $lRes["name"]."<br />";
		
		$out['items'] = $items;
	  }
	  else $out['items'] = 'товаров нет';
}
if ($action=='add_goods') {
	$id = (int)$_POST['id'];
	if (count($_POST['goods'])>0) foreach ($_POST['goods'] as $v) mysql_query("INSERT INTO mod_catalog_related_products (`item_id`, `related_to`) VALUES ('".$id."', '".(int)$v."')");

	$Db->query="SELECT `name` FROM `mod_catalog`
				LEFT JOIN `mod_catalog_related_products` ON (mod_catalog_related_products.related_to = mod_catalog.id)
				WHERE `item_id`='".$id."'";
	$Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) $items.= $lRes["name"]."<br />";
		
		$out['items'] = $items;
	  }
	  else $out['items'] = 'товаров нет';
}


if ($action=='show_goods') {

	$id = (int)$_POST['id'];
	$cat = (int)$_POST['cat'];
	$related = array();
	
	// сначала вытаскиваем айди тех товаров, которые уже подкреплены
	$Db->query="SELECT `id` FROM `mod_catalog_related_products` WHERE `item_id`='".$id."'";
	$Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) $related[] = $lRes["id"];
	  }

	$Db->query="SELECT `id`, `name`, `price` FROM `mod_catalog` WHERE `act`='1' AND `parent`='".$cat."' ORDER BY `name`";
	$Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		$items = '';
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult))
		{
			if (in_array($lRes["id"], $related)) $chek = ' selected="selected"'; else $chek = '';
			$items.= '<option value="'.$lRes["id"].'"'.$chek.'>'.$lRes["name"].' ('.$lRes["price"].' руб.)</option>';
		}
		 
		$out['items'] = $items;
		
		$out['status'] = 1;
	  }
}

if ($action=='show_cat') {
	  $Db->query="SELECT `id`, `name`, `parent`, `act`, `rank` FROM `mod_catalog_cat` WHERE `act`='1' ORDER BY `parent`,`rank`";
	  $Db->query();
	  if (mysql_num_rows($Db->lQueryResult)>0) {
		while ($lRes=mysql_fetch_assoc($Db->lQueryResult)) 
		  $data[$lRes['parent']][] = $lRes;
		
		$data = getTree($data, 0);
		$out['items'] = getSelectOptions($data);
		
		$out['status'] = 1;
	  }
}


if ($action=='save_cat') {
	
	$id = (int)$_POST['id'];
	$parent = (int)$_POST['parent'];
	$mod = $filter->html_filter($_POST['mod']);
	if (!empty($id) && !empty($parent) && !empty($mod))
	{
		$Db->query="UPDATE ".$DBPrefix."mod_catalog SET `$mod`='$parent' WHERE `id`='".$id."' LIMIT 1";
		$Db->query();
	}
}

echo json_encode($out);


function getSelectOptions($data,$parent,$sub,$tire,$self_id) { 
  foreach ($data as $k=>$v) {
		if ($v['id']==$parent) $chek = " selected='selected'"; 
    else $chek = "";
    if ($v['id']!=$self_id) {
      $sub.='<option value="'.$v["id"].'"'.$chek.'>'.$tire.$v['name'].'</option>';
      if (isset($v['childs'])) 
        $sub=getSelectOptions($v['childs'],$parent,$sub, $tire."----",$self_id);
    }
  }
	return $sub;
}

function getExtension1($filename) {
    return end(explode(".", $filename));
  }