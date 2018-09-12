<?php
/**
 * Сохранение формы редактирования
 * @param object &$Db              Обёртка над БД
 * @param string $tablename        Название таблицы, в которую пишем
 * @param array $preparedData     Массив подготовленных для записи данных
 * @param array $filterExceptions Массив имён-исключений полей из массива данных
 * @param string $mod              Модуль, из которого вызывается функция
 * @param string [$action='list']  Действие, выполняемое после сохранения формы
 */
function saveEditForm (&$Db, $tablename, $preparedData, $filterExceptions=array()) {
  $query_params = prepareEditForm($preparedData, $filterExceptions);       
  $Db->query = "INSERT INTO `$tablename` (".$query_params['params_query'].") VALUES (".$query_params['values_query'].") ON DUPLICATE KEY UPDATE ".$query_params['params_onduplicate'];
//  var_dump($Db->query);
  if ($Db->query()) return true;
  else {
    echo '</br>';
    var_dump($Db->query);
  }
}

/**
 * Преобразование подготовленных для сохранения данных в части sql-запроса
 * @param  array $preparedData     Массив подготовленных для записи данных
 * @param array $filterExceptions Массив имён-исключений полей из массива данных
 * @return array Массив, состоящий из трёх строк - частей sql-запроса
 */
function prepareEditForm($preparedData, $filterExceptions=array()) {
  $out = array();
  $form_fields = $preparedData;
  unset($form_fields['submit']);

  $filter = new filter;
  foreach ($form_fields as $k=>$v) {
    if (!in_array($k,$filterExceptions)) {
      $v = $filter->html_filter($v);
    } else {
      $v = mysql_escape_string($v);
    }
    $out['params_query'] .= "`$k`,";
    $out['values_query'] .= "'$v',";
    $out['params_onduplicate'] .= "`$k`=VALUES(`$k`),";
  }    

  $out['params_query'] = clearLastComma ($out['params_query']);
  $out['values_query'] = clearLastComma ($out['values_query']);
  $out['params_onduplicate'] = clearLastComma ($out['params_onduplicate']);

  //хак для вставки MySQL даты
  $out['values_query'] = str_replace("'NOW()'","NOW()",$out['values_query']);

  return $out;
}

/**
 * Функция обрезки последней запятой в части sql-запроса
 * @param  string $str Исходная строка
 * @return string Обработанная строка
 */
function clearLastComma ($str) {
  return substr($str,0,-1);
}

/**
 * Универсальная функция создания изображений
 * @param string $original      Путь к исходному изображению
 * @param string $generatedName Сгенерированное имя нового изображения
 * @param array $properties    Массив массивов свойств новых изображений
 */
function createImages ($original, $generatedName, $properties, $type = 'jpg') {
  foreach ($properties as $v) {
    $folder_name = $_SERVER['DOCUMENT_ROOT'].'/upload/'.$v['folder'];
    if (!file_exists($folder_name)) mkdir($folder_name,0755);
    
    switch ($v['function']) {
      case 1:
        imgResize($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], 0xFFFFFF, 90);
        break;
      case 2:
        create_thumbnail($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], (bool)$v['do_cut']);
        break;
      case 3:
        move_uploaded_file($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type);
    }
  }
}
function createImagestwo ($original, $generatedName, $properties, $type = 'jpg') {
  foreach ($properties as $v) {
    $folder_name = $_SERVER['DOCUMENT_ROOT'].'/upload/'.$v['folder'];
    if (!file_exists($folder_name)) mkdir($folder_name,0755);
    
    switch ($v['function']) {
      case 1:
        imgResize($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], 0xFFFFFF, 90);
        break;
      case 2:
        create_thumbnail($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], (bool)$v['do_cut']);
        break;
      case 3:
        move_uploaded_file($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type);
    }
  }
}
function createImagestwo_png ($original, $generatedName, $properties, $type = 'png') {
  foreach ($properties as $v) {
    $folder_name = $_SERVER['DOCUMENT_ROOT'].'/upload/'.$v['folder'];
    if (!file_exists($folder_name)) mkdir($folder_name,0755);
    
    switch ($v['function']) {
      case 1:
        imgResize($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], 0xFFFFFF, 90);
        break;
      case 2:
        create_thumbnail($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type, $v['width'], $v['height'], (bool)$v['do_cut']);
        break;
      case 3:
        move_uploaded_file($original, $folder_name.'/'.$v['prefix'].$generatedName.'.'.$type);
    }
  }
}

/**
 * Функция по постройке списка элементов
 * Можно задать постфикс действий (переход на страницу редактирования, ранги), указав его в $extra['action_postfix']
 * @param array $data  массив отображаемых данных
 * @param string $otstup внутреняя переменная для создания отступов дочерним страницам
 * @param string $mod название модуля (для ссылки)
 * @param array $fields массив полей для таблицы
 * @param array $extra массив дополнительных параметров
 */ 
 function getTreeTable($data, $otstup, $mod, $fields = array('id','name','date','rank','conf'), $extra = array()) { 
	$rank = sizeof($data, $num);
	
	foreach ($data as $k=>$v) {
    $return .= '<tr>';
    
    foreach ($fields as $field_name) {
      $return .= '<td>';
      switch ($field_name) {
        case 'id':
          $return .= $v['id'];
          break;
          
        case 'mail':
          $return .= $v['mail'];
          break;
          
        case 'email':
          $action = (!empty($extra['action_postfix'])) ? 'edit'.$extra['action_postfix'] : 'edit';
          $action = (!empty($extra['alter_action'])) ? $extra['alter_action'] : $action;
          $return .= $otstup.'<a href="index.php?mod='.$mod.'&action='.$action.'&id='.$v['id'].'" class="text-red">'.$v['email'].'</a>';
          break;

        case 'last_online':
          if ($v['date']!='0000-00-00 00:00:00') $return .= formatedDate($v['date']);
          else $return .= 'никогда';
          break;
          
        case 'name':
          $action = (!empty($extra['action_postfix'])) ? 'edit'.$extra['action_postfix'] : 'edit';
          $action = (!empty($extra['alter_action'])) ? $extra['alter_action'] : $action;
          $return .= $otstup.'<a href="index.php?mod='.$mod.'&action='.$action.'&id='.$v['id'].'" class="text-red">'.$v['name'].'</a>';
          break;
          
        case 'date_only':
          $return .= formatedDate($v['date']);
          break;
          
        case 'status':
          $return .= $extra['status_arr'][$v['status']];
          break;
          
        case 'date':
          $return .= formatedDate($v['edit_date']).'&nbsp;&nbsp;/&nbsp;&nbsp;<b>'.$v['username'].'</b>';
          break;
          
        case 'rank':
          $action_up = (!empty($extra['action_postfix'])) ? 'rankup'.$extra['action_postfix'] : 'rankup';
          $action_down = (!empty($extra['action_postfix'])) ? 'rankdown'.$extra['action_postfix'] : 'rankdown';
          if ($v['rank']!=$rank) 
            $down = "<a href='index.php?mod=".$mod."&action=$action_down&id=".$v['id']."&cat=".$v['parent']."&rank=".$v['rank']."' title='Двигать вниз' class='text-red'><i class='fa fa-chevron-down'></i></a>";
          else 
            $down = '<i class="fa fa-chevron-down"></i>';
          
          if ($v['rank']!=1) 
            $up = "<a href='index.php?mod=$mod&action=$action_up&id=".$v['id']."&cat=".$v['parent']."&rank=".$v['rank']."' align='middle' title='Двигать вверх' class='text-red'><i class='fa fa-chevron-up'></i></a>";
          else 
            $up = '<i class="fa fa-chevron-up"></i>';
          
          $return .= $up.$down;
          break;
          
        case 'conf':
          $return .= '<input type="hidden" value="0" name="act['.$v['id'].']" />';
          if ($v['act']!=0) 
            $return.='<input type="checkbox" class="checkboxact" value="1" name="act['.$v['id'].']" checked="checked"	/> '; 
          else 
            $return.='<input type="checkbox" value="1" name="act['.$v['id'].']" class="checkboxact" /> ';
		      $return.='<input type="checkbox" value="1" class="checkbox" name="delete['.$v['id'].']" />';
          break;
			  
		case 'conf_person':          
          $return .= '<input type="hidden" value="0" name="act['.$v['id'].']" />';
          if ($v['act']!=0) 
            $return.='<input type="checkbox" class="checkboxact" value="1" name="act['.$v['id'].']" checked="checked"	/> '; 
          else 
            $return.='<input type="checkbox" value="1" name="act['.$v['id'].']" class="checkboxact" /> ';
          
          $return .= '<input type="hidden" value="0" name="confirmed['.$v['id'].']" />';
          if ($v['confirmed']!=0) 
            $return.='<input type="checkbox" class="checkboxact" value="1" name="confirmed['.$v['id'].']" checked="checked"	/> '; 
          else 
            $return.='<input type="checkbox" value="1" name="confirmed['.$v['id'].']" class="checkboxact" /> ';
          
		      $return.='<input type="checkbox" value="1" class="checkbox" name="delete['.$v['id'].']" />';
        break;  
          
        case 'delete':
		      $return.='<input type="checkbox" value="1" class="checkbox" name="delete['.$v['id'].']" />';
          break;
          
        case 'cat':
          if (is_array($v['parent'])) {
            $parents_names = '';
            foreach ($v['parent'] as $parent_id) {
              $parents_names .= $extra['cat'][$parent_id].', ';
            }
            $parents_names = substr($parents_names,0,-2);
            $return .= $parents_names;
          } else {
            if ($v['parent']>0) $return .= $extra['cat'][$v['parent']];
            else $return .= 'без категории';
          }
          break;
          
        case 'summ':
          $return .= $v['summ'];
      }
      $return .= '</td>';
    }
    
    if (isset($v['childs'])) $return.=getTreeTable($v['childs'], $otstup."&nbsp;&nbsp;&nbsp;&nbsp;", $mod, $fields, $extra);
    
    $return .= '</tr>';
  }
	return $return;
}

/**
 * Генерирует html отображение каждого поля для формы редактирования материала
 * @param  array $v               Массив параметров текущего поля
 * @param  array $edit_parameters Массив значений полей текущего материала
 * @param  object &$Db             Ссылка на класс работы с базой
 * @param  string $mod             Текущий модуль
 * @return string   html отображение поля
 */
function getEditformFieldView($v,$edit_parameters,&$Db, $mod) {
  $v['Comment'] = explode('|',$v['Comment']);
  $thisTitle = $v['Comment'][0];
  $thisType = $v['Comment'][1];
  $thisPosition = $v['Comment'][2];
  $outView = '';
  switch ($thisType) {
// ===== checkbox ===== //
// генерирует пару скрытое поле + чекбокс
// УНИВЕРСАЛЬНОЕ
    case 'checkbox':
      $thisChecked = '';
      if ($edit_parameters[$v['Field']]) $thisChecked = ' checked';
      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">
            <input type="hidden" name="'.$v['Field'].'" value="0">
            <input type="checkbox" id="form_'.$v['Field'].'" name="'.$v['Field'].'" value="1" '.$thisChecked.'> '.$thisTitle.'
          </label>
        </div>';
      break;
// ===== /checkbox ===== //



// ===== select ===== //
// генерирует пару скрытое поле с префиксом old + селект
// при построении запрашивает элементы из таблицы, указанной в качестве 3го параметра в поле комментария
// ЗАВИСИМОСТЬ: у таблицы с предками должны быть поля `anchor`, `parent`, `rank`
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
    case 'select':
      $select_root = ($mod=='content') ? 'Корень сайта' : 'не назначено';
      $thisParentTable = $v['Comment'][3];
      if ($edit_parameters[$v['Field']]==0) $no_parent_selected = ' selected';
      $out = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="hidden" name="old'.$v['Field'].'" value="'.$edit_parameters[$v['Field']].'" />
          <select id="form_'.$v['Field'].'" name="'.$v['Field'].'" class="form-control">
            <option value="0" '.$no_parent_selected.'>'.$select_root.'</option>';
      $data = '';
      $query = mysql_query("SELECT `id`,`anchor`,`name`,`parent` FROM `$thisParentTable` ORDER BY `name`, `parent`,`rank`");
      if (($query)&&(mysql_num_rows($query)>0)){
        while ($lRes=mysql_fetch_assoc($query)) $data[$lRes['parent']][] = $lRes;
        $data = getTree($data, 0, "id");
        $out .= getSelectOptions($data, $edit_parameters[$v['Field']], "", "--", $edit_parameters['id']);
      }
      $out .= '
          </select>
        </div>';
      $outView = $out;						
      break;
// ===== /select ===== //



// ===== static_select ===== //
// генерирует статический селект без вложенности
// при построении запрашивает элементы из таблицы, указанной в качестве 3го параметра в поле комментария
// УНИВЕРСАЛЬНОЕ
    case 'static_select':
      $select_root = 'не назначено';
      $thisParentTable = $v['Comment'][3];
      if ($edit_parameters[$v['Field']]==0) $no_parent_selected = ' selected';
      $out = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <select id="form_'.$v['Field'].'" name="'.$v['Field'].'" class="form-control">
            <option value="0" '.$no_parent_selected.'>'.$select_root.'</option>';
      $data = '';
      $query = mysql_query("SELECT `id`,`name` FROM `$thisParentTable` ORDER BY `name`");
      if (($query)&&(mysql_num_rows($query)>0)){
        while ($lRes=mysql_fetch_assoc($query)) {
          $thisSelected = ($lRes['id']==$edit_parameters[$v['Field']]) ? 'selected' : '';
          $out .= '<option value="'.$lRes['id'].'" '.$thisSelected.'>'.$lRes['name'].'</option>';
        }
      }
      $out .= '
          </select>
        </div>';
      $outView = $out;						
      break;
// ===== /static_select ===== //



// ===== select_multiple ===== //
// генерирует мультиселект
// при построении запрашивает элементы из таблицы, указанной в качестве 3го параметра в поле комментария
// связи описаны в таблице, указанной в качестве 4го параметра в поле комментария
// TODO: не изменяет ранги у элементов при перемещении между категориями
// ЗАВИСИМОСТЬ: у таблицы с предками должны быть поля `anchor`, `parent_t`, `rank`
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
    case 'select_multiple':
      $select_root = ($mod=='content') ? 'Корень сайта' : 'не назначено';
      $thisParentTable = $v['Comment'][3];
      $relationsTable = $v['Comment'][4];

      $out = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          
          <select id="form_'.$v['Field'].'" name="'.$v['Field'].'[]" class="form-control" multiple size="10">
            <option value="0">'.$select_root.'</option>';

      //массив из всех элементов
      $data = array();
      $query = mysql_query("SELECT `id`,`anchor`,`name`,`parent` FROM `$thisParentTable` ORDER BY `parent`,`rank`");
      while ($lRes=mysql_fetch_assoc($query)) $data[$lRes['parent']][] = $lRes;
      $data = getTree($data, 0, "id");

      //массив связей
      $relations = array();
      $query = mysql_query("SELECT `rel` FROM `$relationsTable` WHERE `item`='".$edit_parameters['id']."' ");
      while ($lRes=mysql_fetch_assoc($query)) $relations[] = $lRes['rel'];

      $out .= getMultiSelectOptions($data, $relations, "", "--");
      $out .= '
          </select>
        </div>';
      $outView = $out;						
      break;
// ===== /select_multiple ===== //



// ===== text ===== //
// генерирует обычное текстовое поле 
// причём делает его обязательным к заполнению если это поле `name`
// УНИВЕРСАЛЬНОЕ
    case 'text':
      if ($v['Field']=='name') $input_text_required = 'required';
      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="text" class="form-control" id="form_'.$v['Field'].'" name="'.$v['Field'].'" value="'.htmlspecialchars(stripslashes($edit_parameters[$v['Field']])).'" '.$input_text_required.' />
        </div>';
      break;
// ===== /text ===== //



// ===== only_text ===== //
// выводит информацию из базы в виде нередактируемого текстового инпута
// УНИВЕРСАЛЬНОЕ
    case 'only_text':
      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="text" class="form-control" id="form_'.$v['Field'].'" name="'.$v['Field'].'" value="'.htmlspecialchars(stripslashes($edit_parameters[$v['Field']])).'" disabled />
        </div>';
      break;
// ===== /only_text ===== //



// ===== date_picker ===== //
// генерирует обычное текстовое поле 
// причём делает его обязательным к заполнению если это поле `name`
// УНИВЕРСАЛЬНОЕ
    case 'date_picker':
//      $edit_parameters[$v['Field']] = explode(' ',$edit_parameters[$v['Field']]);
//      $edit_parameters[$v['Field']] = $edit_parameters[$v['Field']][0];
      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="text" class="form-control date_picker" id="form_'.$v['Field'].'" name="'.$v['Field'].'" value="'.htmlspecialchars(stripslashes($edit_parameters[$v['Field']])).'" />
          <span class="help-block">В формате год-месяц-день часы:минуты:секунды</span>
        </div>';
      break;
// ===== /date_picker ===== //



// ===== content ===== //
// генерует textarea для последующего подключения CKEditor
// УНИВЕРСАЛЬНОЕ
    case 'content':
      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <textarea class="form-control ckeditor" id="form_'.$v['Field'].'" name="'.$v['Field'].'" rows="10" cols="80">
            '.htmlspecialchars(stripslashes($edit_parameters[$v['Field']])).'
          </textarea>
        </div>';
      break;
// ===== /content ===== //



// ===== cover ===== //
// TODO: дописать вывод изображения если есть загруженный файл
// генерирует поле для загрузки изображения
// либо скрытое поле + изображение с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// для вывода использует изображение с префиксом bg
// действие при удалении - delete_cover_$type, где $type - типа материала в модуле (указывается в настройках изображений $image_props)
    case 'cover':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        //изображение имеется
        global $image_props;
        $type = $v['Comment'][3];
        
        //хак для изображений png в обложке (в этом случае выводим с префиксом sm и с расширением png)
        $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/bg'.$edit_parameters[$v['Field']].'.jpg';
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
          $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/sm'.$edit_parameters[$v['Field']].'.jpg';
          if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
            $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/cover'.$edit_parameters[$v['Field']].'.jpg';
            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
              $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/sm'.$edit_parameters[$v['Field']].'.png';
            }
          }
        }
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <img class="cover_img" src="'.$path.'" />
              <a class="cover_delete" href="#" title="Удалить обложку" onclick="UniversalDelete(\''.$mod.'\',\'delete_cover_'.$type.'\',\''.$edit_parameters['id'].'\',\'обложку\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /cover ===== //
// ===== cover 2 ===== //
// TODO: дописать вывод изображения если есть загруженный файл
// генерирует поле для загрузки изображения
// либо скрытое поле + изображение с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// для вывода использует изображение с префиксом bg
// действие при удалении - delete_cover_$type, где $type - типа материала в модуле (указывается в настройках изображений $image_props)
    case 'cover_two':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        //изображение имеется
        global $image_props;
        $type = $v['Comment'][3];
        
        //хак для изображений png в обложке (в этом случае выводим с префиксом sm и с расширением png)
        $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/bg'.$edit_parameters[$v['Field']].'.jpg';
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
          $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/sm'.$edit_parameters[$v['Field']].'.jpg';
          if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
            $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/cover'.$edit_parameters[$v['Field']].'.jpg';
            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
              $path = '/upload/'.$image_props[$mod][$type][0]['folder'].'/sm'.$edit_parameters[$v['Field']].'.png';
            }
          }
        }
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <img class="cover_img" src="'.$path.'" />
              <a class="cover_delete" href="#" title="Удалить обложку" onclick="UniversalDelete(\''.$mod.'\',\'delete_cover_two_'.$type.'\',\''.$edit_parameters['id'].'\',\'обложку\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
	  }
      break;
// ===== /cover 2 ===== //

// ===== file ===== //
// генерирует поле для загрузки произвольного файла
// либо скрытое поле + ссылка на файл с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// в базе хранится полный путь к файлу
// действие при удалении - delete_file_$type, где $type - 3й параметр в комментарии поля
    case 'file':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        $type = $v['Comment'][3];
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <a href="'.$edit_parameters[$v['Field']].'" target="_blank" title="Откроется в новом окне">файл загружен</a>
              <a class="cover_delete" href="#" title="Удалить файл" onclick="UniversalDelete(\''.$mod.'\',\'delete_file_'.$type.'\',\''.$edit_parameters['id'].'\',\'файл\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /file ===== //
// ===== file 2 ===== //
// генерирует поле для загрузки произвольного файла
// либо скрытое поле + ссылка на файл с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// в базе хранится полный путь к файлу
// действие при удалении - delete_file_$type, где $type - 3й параметр в комментарии поля
    case 'file_two':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        $type = $v['Comment'][3];
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <a href="'.$edit_parameters[$v['Field']].'" target="_blank" title="Откроется в новом окне">файл загружен</a>
              <a class="cover_delete" href="#" title="Удалить файл" onclick="UniversalDelete(\''.$mod.'\',\'delete_file_'.$type.'\',\''.$edit_parameters['id'].'\',\'файл\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /file 2 ===== //
// ===== file 3 ===== //
// генерирует поле для загрузки произвольного файла
// либо скрытое поле + ссылка на файл с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// в базе хранится полный путь к файлу
// действие при удалении - delete_file_$type, где $type - 3й параметр в комментарии поля
    case 'file_three':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        $type = $v['Comment'][3];
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <a href="'.$edit_parameters[$v['Field']].'" target="_blank" title="Откроется в новом окне">файл загружен</a>
              <a class="cover_delete" href="#" title="Удалить файл" onclick="UniversalDelete(\''.$mod.'\',\'delete_file_'.$type.'\',\''.$edit_parameters['id'].'\',\'файл\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /file 3 ===== //
// ===== file 4 ===== //
// генерирует поле для загрузки произвольного файла
// либо скрытое поле + ссылка на файл с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// в базе хранится полный путь к файлу
// действие при удалении - delete_file_$type, где $type - 3й параметр в комментарии поля
    case 'file_four':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        $type = $v['Comment'][3];
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <a href="'.$edit_parameters[$v['Field']].'" target="_blank" title="Откроется в новом окне">файл загружен</a>
              <a class="cover_delete" href="#" title="Удалить файл" onclick="UniversalDelete(\''.$mod.'\',\'delete_file_'.$type.'\',\''.$edit_parameters['id'].'\',\'файл\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /file 4 ===== //
// ===== file 5 ===== //
// генерирует поле для загрузки произвольного файла
// либо скрытое поле + ссылка на файл с кнопкой удаления
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// в базе хранится полный путь к файлу
// действие при удалении - delete_file_$type, где $type - 3й параметр в комментарии поля
    case 'file_one':
      if ($edit_parameters[$v['Field']]=='') {
        //новое изображение
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'">
          </div>';
      } else {
        $type = $v['Comment'][3];
        
        $outView = '
          <div class="form-group">
            <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
            <div class="cover_img_wrap">
              <a href="'.$edit_parameters[$v['Field']].'" target="_blank" title="Откроется в новом окне">файл загружен</a>
              <a class="cover_delete" href="#" title="Удалить файл" onclick="UniversalDelete(\''.$mod.'\',\'delete_file_'.$type.'\',\''.$edit_parameters['id'].'\',\'файл\')"><i class="fa fa-trash"></i></a>
            </div>
            <input type="hidden" name="'.$v['Field'].'_load" value="'.$edit_parameters[$v['Field']].'" />
          </div>';
      }
      break;
// ===== /file 5 ===== //

// ===== additional_images ===== //
// генерирует интерфейс работы с дополнительными изображениями
// УНИВЕРСАЛЬНОЕ
// ТРЕБУЕТ написание альтернативной обработки при сохранении
//
//          Особенности работы:
// для вывода использует изображение с префиксом bg
// действие при удалении - delete_image_$type, где $type - типа материала в модуле (указывается в настройках изображений $image_props)
    case 'additional_images':
      $additional_images_table = $v['Comment'][3]; //из какой таблицы брать картинки
      $type = $v['Comment'][4]; //какой тип надо брать из $image_props, указывается в комментах на 4м месте

      if ($edit_parameters['id']>0) {
        //получаем и формируем список ранее загруженных картинок
        $query = mysql_query("SELECT * FROM `$additional_images_table` WHERE `item`='".$edit_parameters['id']."'");
		if ($additional_images_table=="mod_gallery_file") $pref = "sm"; else  $pref = "med";
        if (mysql_num_rows($query)>0) {
          $early_uploaded_images = '<label>Загруженные изображения:</label><div class="additional_images_wrap">';
          global $image_props;
          while($lRes = mysql_fetch_assoc($query)) {
			
            $early_uploaded_images .= '
                <img class="cover_img" src="/upload/'.$image_props[$mod][$type][0]['folder'].'/'.$pref.''.$lRes['name'].'.jpg" />
                <a class="cover_delete" href="#" title="Удалить изображение" onclick="UniversalDelete(\''.$mod.'\',\'delete_image_'.$type.'\',\''.$lRes['id'].'\',\'изображение\')"><i class="fa fa-trash"></i></a>';
			  if ($v['Comment'][3]=='mod_gallery_file') $early_uploaded_images .= '<input type="text" class="form-control" name="gallery_file_name['.$lRes['id'].']" value="'.$lRes['alt'].'">';
          }
          $early_uploaded_images .= '</div>';
        }
      }

      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="file" id="form_'.$v['Field'].'" name="'.$v['Field'].'[]" multiple accept="image/jpeg">
          '.$early_uploaded_images.'
        </div>';
      break;
// ===== /additional_images ===== //



// ===== related_products ===== //
// генерирует интерфейс работы со связанными товарами в каталоге
// ТОЛЬКО mod_catalog
// ТРЕБУЕТ написание альтернативной обработки при сохранении
    case 'related_products':
      $related_products_table = $v['Comment'][3]; //в какой таблице находится информация о сопутствующих товарах

      if ($edit_parameters['id']>0) {
        $related_objects_arr = array();
        
        //получаем и формируем список ранее привязанных товаров
        $query = mysql_query("SELECT $related_products_table.related_to, mod_catalog.name 
        FROM `$related_products_table` 
        LEFT JOIN `mod_catalog` ON ($related_products_table.related_to=mod_catalog.id)
        WHERE $related_products_table.item_id='".$edit_parameters['id']."'");
        if (mysql_num_rows($query)>0) {
          while($lRes = mysql_fetch_assoc($query)) {
            $related_objects_arr[$lRes['related_to']] = $lRes['name'];
          }
        }
        $query = mysql_query("SELECT $related_products_table.item_id, mod_catalog.name 
        FROM `$related_products_table` 
        LEFT JOIN `mod_catalog` ON ($related_products_table.item_id=mod_catalog.id)
        WHERE $related_products_table.related_to='".$edit_parameters['id']."'");
        if (mysql_num_rows($query)>0) {
          while($lRes = mysql_fetch_assoc($query)) {
            $related_objects_arr[$lRes['item_id']] = $lRes['name'];
          }
        }
        if (count($related_objects_arr)>0) {
          $early_related_products = '<label>Уже связанные товары</label><div class="related_products_wrap"><ul class="list-unstyled">';
          foreach ($related_objects_arr as $obj_id=>$obj_name) {
            $early_related_products .= '
            <li>
              <a href="index.php?mod=catalog&action=edit&id='.$obj_id.'" target="_blank">'.$obj_name.'</a>
              <a class="related_products_delete" href="#" title="Удалить связь" onclick="UniversalDelete(\''.$mod.'\',\'delete_related_product\',\''.$edit_parameters['id'].'-'.$obj_id.'\',\'связь\')"><i class="fa fa-trash"></i></a>
            </li>';
          }
          $early_related_products .= '</ul></div>';
        }
      }

      $outView = '
        <div class="form-group">
          <label for="form_'.$v['Field'].'">'.$thisTitle.'</label>
          <input type="text" class="form-control" id="form_'.$v['Field'].'" name="'.$v['Field'].'" />
          <span class="help-block">Введите через запятую ID товаров, с которыми нужно связать текущий товар</span>
          '.$early_related_products.'
        </div>';
      break;
// ===== /related_products ===== //



// ===== additional_params ===== //
// дополнительные параметры у товаров
// ТОЛЬКО mod_catalog
// ТРЕБУЕТ написание альтернативной обработки при сохранении
    case 'additional_params':
      $additional_params_table = $v['Comment'][3]; //в какой таблице находится информация о дополнительных параметрах товара

      if ($edit_parameters['id']>0) {
        $additional_params_arr = array();
        //получаем и формируем список ранее привязанных товаров
        $query = mysql_query("SELECT `name`,`value` FROM `$additional_params_table`
        WHERE `good`='".$edit_parameters['id']."'");
        if (mysql_num_rows($query)>0) {
          $early_additional_params = '';
          while($lRes = mysql_fetch_assoc($query)) {
            $early_additional_params .= '
            <div class="pre_gen">
              <input type="text" class="form-control" name="ap_names[]" value="'.htmlspecialchars(stripslashes($lRes['name'])).'" />
              <input type="text" class="form-control" name="ap_values[]" value="'.htmlspecialchars(stripslashes($lRes['value'])).'" />
              <input type="button" class="btn btn-danger delete_additional_param" value="Удалить" />
            </div>';
          }
        }
      }

      $outView = '
        <div class="form-group">
          <label class="label-control">'.$thisTitle.'</label>
          <div class="additional_params_wrap form-inline">
            '.$early_additional_params.'
          </div>
          <input type="button" class="btn btn-success" id="add_new_additional_param" value="Добавить ещё" />
        </div>';
      break;
// ===== /additional_params ===== //



// ===== params ===== //
// вывод параметров фильтра
// ТОЛЬКО mod_filter
    case 'params':
      $params_table = $v['Comment'][3]; 

      if ($edit_parameters['id']>0) {
        $params_arr = array();
        //получаем и формируем таблицу с параметрами данного фильтра
        $query = mysql_query("SELECT `id`,`name` FROM `$params_table`
        WHERE `parent`='".$edit_parameters['id']."'");
        if (($query)&&(mysql_num_rows($query)>0)) {
          $params_list = '
          <table class="table table-hover">
            <tr>
              <th width="3%" class="text-center"><h4>ID</h4></th>
              <th><h4>Название</h4></th>
              <th width="5%"></th>
            </tr>';
          while($lRes = mysql_fetch_assoc($query)) {
            $params_list .= '
            <tr>
              <td class="text-center">'.$lRes['id'].'</td>
              <td>'.$lRes['name'].'</td>
              <td><a class="btn btn-danger" href="#" title="Удалить параметр" onclick="UniversalDelete(\''.$mod.'\',\'delete_filter_params\',\''.$lRes['id'].'\',\'параметр\')">удалить</a></td>
            </tr>';
          }
          $params_list .= '</table>';
        }
      }

      if ($edit_parameters['id']>0) {    
        $outView = '
          <div class="form-group">
            <label class="label-control">'.$thisTitle.'</label>
            '.$params_list.'
            <p><a class="btn btn-success" href="index.php?mod='.$mod.'&action=edit_params&parent='.$edit_parameters['id'].'">Добавить параметр</a></p>
          </div>';
      } else {
        $outView = '
          <div class="form-group">
            <label class="label-control">'.$thisTitle.'</label>
            <p>Доступно только в режиме редактирования</p>
          </div>';
      }
      break;
// ===== /params ===== //



// ===== related_filters ===== //
// генерирует интерфейс работы с фильтрами в редактировании товара
// ТОЛЬКО mod_catalog С ПОДКЛЮЧЁННЫМ mod_filter
// ТРЕБУЕТ написание альтернативной обработки при сохранении
    case 'related_filters':
      $relations_table = $v['Comment'][3];

      if ($edit_parameters['id']>0) {
        //получаем все фильтры, все параметры фильтров и все значения привязок
        //причём, тут вложенность - фильтры=>параметры_фильтров=>связи
        //т.е. если нет фильтров или параметров - ничего не выведется
		//если у фильтра установлен параметр slide тогда выводим текстовое поле для заполнения (только числовой формат)
		
        $query = mysql_query("
			SELECT mod_filter.id, mod_filter.name, mod_filter.slide 
			FROM `mod_filter_relations` 
			LEFT JOIN `mod_filter` ON (mod_filter.id=mod_filter_relations.item) 
			WHERE mod_filter_relations.rel='".$edit_parameters['parent']."' AND mod_filter.id IS NOT NULL");
        if (($query)&&(mysql_num_rows($query)>0)) {
          //фильтры
          $filters_arr = array();
          while($lRes = mysql_fetch_assoc($query)) {
            $filters_arr[$lRes['id']] = $lRes['name'];
            $filters_arr_slide[$lRes['id']] = $lRes['slide'];
          }
          
          //параметры фильтров
          $query = mysql_query("SELECT `id`,`name`,`parent` FROM `mod_filter_params`");
          if (($query)&&(mysql_num_rows($query)>0)) {
            //параметры
            $params_arr = array();
            while($lRes = mysql_fetch_assoc($query)) {
              $params_arr[$lRes['parent']][$lRes['id']] = $lRes['name'];
            }

            //привязки товар->параметр фильтра
            $relations_arr = array();
            $query = mysql_query("SELECT id, filter_param_id, filter_slide FROM `mod_catalog_related_filters` WHERE `good`='".$edit_parameters['id']."'");
            if (($query)&&(mysql_num_rows($query)>0)) {
              while($lRes = mysql_fetch_assoc($query)) {
                $relations_arr[$lRes['filter_param_id']] = $lRes['filter_param_id'];
                $relations_arr_slide[$lRes['filter_param_id']] = $lRes['filter_slide'];
              }
            }
          }
          $list = '';
		
		
		foreach ($filters_arr as $filter=>$filter_name) {
            $list .= '
            <div class="form-group form-inline filter_params_select">
              <label class="label-control" for="good_filters_'.$filter.'">'.$filter_name.':</label>';

			if ($filters_arr_slide[$filter]!=1)
			{			
			    $list .= '<select class="form-control" name="good_filters['.$filter.']" id="good_filters_'.$filter.'">
                <option value="0">не привязан</option>';
				if (count($params_arr[$filter])>0) {
				  foreach ($params_arr[$filter] as $param=>$param_name) {
					$selected_param = (in_array($param,$relations_arr)) ? 'selected' : '';
					$list .= '
					  <option value="'.$param.'" '.$selected_param.'>'.$param_name.'</option>';
				  }
				}
				$list .= '
				  </select>';
			}
			else
			{
				$keys = array_keys($params_arr[$filter]);
				$firstKey = $keys[0];
				
				$list .= '<input type="text" value="'.$relations_arr_slide[$firstKey].'" class="form-control" style="margin-left: 12px;" name="good_filters_slide['.$filter.']" id="good_filters_'.$filter.'" />
						<input type="hidden" name="good_filters_hidden['.$filter.']" value="'.$firstKey.'">';
			}
           $list .= '</div>';
          }
        } 
      }
	  else {
          $list = '<p>Доступно только в режиме редактирования</p>';
        }
   
      $outView = '
        <div class="form-group">
          <label class="label-control">'.$thisTitle.'</label>
        </div>
        '.$list;
      break;
// ===== /related_filters ===== //



// ===== basket ===== //
// рендер корзины
    case 'basket':
      $basket = array();
      $basket_str = explode(';',$edit_parameters[$v['Field']]);
      foreach ($basket_str as $k=>$v) {
        $v = explode('-',$v); // 0 - айди, 1 - кол-во
        $query = mysql_query("SELECT mod_catalog.* FROM `mod_catalog` WHERE `id`='".$v[0]."' LIMIT 1");
        if (($query)&&(mysql_num_rows($query)>0)) {
          //параметры
          $lRes = mysql_fetch_assoc($query);
          $lRes['count'] = $v[1];
			
			if ($edit_parameters['type_price']=='Опт') {
				if ($lRes['price_two']!='0') {
					$lRes['price'] = $lRes['price_two'];
				} else {
					$lRes['price'] = $lRes['price'];
				}
			} else {
				$lRes['price'] = $lRes['price'];
			}
          $lRes['cover'] = ($lRes['cover']=='') ? 'empty' : $lRes['cover'];
//			if ($lRes['price']!='0') { $lRes['price'] = $lRes['price']; }
//			else if ($lRes['price_two']!='0') { $lRes['price'] = $lRes['price_two']; }
//			else { $lRes['price'] = $lRes['price_r']; }
			
		 // if ($lRes['priceold']>0) { //Проверяем есть ли скидка
		//	  $lRes['price'] = $lRes['priceold'];
		//	  $lRes['total_price'] = $lRes['priceold']*$v[1];
		 // } else {
			  $lRes['total_price'] = $lRes['price']*$v[1];
		 // }
          $basket[] = $lRes;
        }
      }
      
      $outView = '
      <table class="table table-hover basket_table">
        <tr>
          <th width="3%" class="text-center"><h4>№</h4></th>
          <th><h4>Название товара</h4></th>
          <th class="text-center"><h4>Артикул</h4></th>
          <th class="text-center"><h4>Цена</h4></th>
          <th class="text-center"><h4>Количество</h4></th>
          <th class="text-center"><h4>Стоимость</h4></th>
        </tr>';
      
      foreach ($basket as $k=>$v) {
        $outView .= '
        <tr>
          <td class="text-center">'.($k+1).'</td>
          <td><img src="/upload/goods/cover'.$v['cover'].'.jpg" height="130"/> <a href="index.php?mod=catalog&action=edit&id='.$v['id'].'" target="_blank" title="Откроется в новом окне" >'.$v['name'].'</a></td>
          <td class="text-center">'.$v['code'].'</td>
          <td class="text-center">'.number_format($v['price'],2,',',' ').' руб.</td>
          <td class="text-center">'.$v['count'].'</td>
          <td class="text-center">'.number_format($v['price']*$v['count'],2,',',' ').' руб.</td>
        </tr>';
      }
      
      $outView .= '
        <tr>
          <td colspan="4" class="text-right"><b>Общая стоимость:</b></td>
          <td class="text-center">'.number_format($edit_parameters['summ'],2,',',' ').' руб.</td>
        </tr>
      </table>';

      break;
// ===== /basket ===== //



// ===== bonus_items ===== //
// рендер корзины
    case 'bonus_items':
      $outView = '
    <div class="form-group">
      <label class="label-control">'.$thisTitle.'</label>';
      $query = mysql_query("SELECT `id`,`name`,`cover` FROM `mod_catalog` WHERE `bonus_item`='1' ORDER BY `name`");
      if (($query)&&(mysql_num_rows($query)>0)) {
        $outView .= '
      <table class="table table-hover bonus_items_table">';
        while($lRes = mysql_fetch_assoc($query)) {
          $lRes['cover'] = ($lRes['cover']=='') ? 'empty' : $lRes['cover'];
          $outView .= '
        <tr>
          <td><img src="/upload/goods/bg'.$lRes['cover'].'.jpg" /><a href="index.php?mod=catalog&action=edit&id='.$lRes['id'].'" target="_blank" title="Откроется в новом окне">'.$lRes['name'].'</a></td>
        </tr>';
        }
        $outView .= '
      </table>';
      }
      else {
        $outView .= '<p>Бонусных товаров нет. Отметить товар как бонусный вы можете на странице редактирования товара.</p>';
      }
      $outView .= '
    </div>';
      break;
// ===== /bonus_items ===== //



// ===== hidden ===== //
// генерирует скрытое поле
// используется для добавления в форму полей id, rank, ...
    case 'hidden':
      $thisPosition = 'hidden';
      $outView = '
        <input type="hidden" name="'.$v['Field'].'" value="'.$edit_parameters[$v['Field']].'" />';
      break;
// ===== /hidden ===== //
  }
  return array('view'=>$outView, 'position'=>$thisPosition);
}

/**
 * Возвращает массив с отсортированными полями по позициям
 * @param  array $array     Массив полей формы
 * @param  array $positions Массив названий позиций
 * @return array Массив с полями, отсортированными по позициям
 */
function getEditformPositions($array, $positions) {
  $out = array_fill_keys($positions, array());  
  foreach ($array as $v) {
    $out[$v['position']][]=$v['view'];
  }
  return $out;
}

/**
 * Генерация элементов выпадающего списка
 * @param  array $data   Массив элементов списка относительно какого-то родительского элемента
 * @param  string $parent ID родительского элемента
 * @param  [[Type]] $sub    [[Description]]
 * @param  [[Type]] $tire   [[Description]]
 * @param  integer $self_id   Айдишник редактируемого элемента
 * @return [[Type]] [[Description]]
 */
function getSelectOptions($data,$parent,$sub,$tire,$self_id) { 
  foreach ($data as $k=>$v) {
		if ($v['id']==$parent) $chek = " selected='selected'"; 
    else $chek = "";
   // if ($v['id']!=$self_id) {
      $sub.='<option value="'.$v["id"].'"'.$chek.'>'.$tire.$v['name'].'</option>';
      if (isset($v['childs'])) 
        $sub=getSelectOptions($v['childs'],$parent,$sub, $tire."----",$self_id);
  //  }
  }
	return $sub;
}

/**
 * Генерация элементов для мультиселекта
 * @param  array $data   Массив элементов списка относительно какого-то родительского элемента
 * @param  string $parent Массив привязанных категорий
 * @param  [[Type]] $sub    [[Description]]
 * @param  [[Type]] $tire   [[Description]]
 * @return [[Type]] [[Description]]
 */
function getMultiSelectOptions($data,$relations,$sub,$tire) { 
  foreach ($data as $k=>$v) {
		if (in_array($v['id'],$relations)) $chek = " selected='selected'"; 
    else $chek = "";
      $sub.='<option value="'.$v["id"].'"'.$chek.'>'.$tire.$v['name'].'</option>';
      if (isset($v['childs'])) 
        $sub=getMultiSelectOptions($v['childs'],$relations,$sub, $tire."----");
  }
	return $sub;
}

/**
 * Записывает статистику
 * @param string $text Текстовое описание того, что записываем
 * @param string $type Тип события
 * 1 - авторизация в системе, 2 - добавление материалов, 3 - заявки с сайта, 4 - комментарии
 */
function writeStat ($text,$type) {
  global $Db;
  $Db->query="INSERT INTO `mod_stat` (`text`, `date`, `type`) VALUES ('$text', NOW(),'$type')";
  $Db->query();
}

function getCatFullList($data,$parent,$num,$sub) { 

  $array_not_rank = array(0, 1, 63, 285, 286);

  if ($num==0) $sub.= '<ul id="red" class="treeview-red">'; else $sub.= '<ul>';
  foreach ($data as $k=>$v) {

		$rank = sizeof($data);
  
		if ($v['rank']!=$rank) 
            $down = "<a href='index.php?mod=catalog&action=rankdown_cat&id=".$v['id']."&cat=".$v['parent']."&rank=".$v['rank']."' title='Двигать вниз' class='text-red'><i class='fa fa-chevron-down'></i></a>";
          else 
            $down = '<i class="fa fa-chevron-down"></i>';
          
          if ($v['rank']!=1) 
            $up = "<a href='index.php?mod=catalog&action=rankup_cat&id=".$v['id']."&cat=".$v['parent']."&rank=".$v['rank']."' align='middle' title='Двигать вверх' class='text-red'><i class='fa fa-chevron-up'></i></a>";
          else 
            $up = '<i class="fa fa-chevron-up"></i>';
	  if (!in_array($v['parent'], $array_not_rank)) $link = $down.$up;	
	  
	  if ($v['act']==1) $act = '<a href="" class="btn btn-primary btn-xs category_act" data-id="'.$v['id'].'" data-act="0"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>'; else $act = '<a href="" class="btn btn-primary btn-xs category_act" data-id="'.$v['id'].'" data-act="1"><i class="fa fa-square-o" aria-hidden="true"></i></a>'; 
	  
      $sub.='<li class="element_'.$v['id'].'"><span>#'.$v['id'].' '.$v['name'].' '.$link.' </span><a href="index.php?mod=catalog&action=edit_cat&id='.$v['id'].'" class="btn btn-warning btn-xs"><i class="fa fa-pencil-square" aria-hidden="true"></i></a> <a href="" data-id="'.$v['id'].'" class="btn btn-success btn-xs category_del"><i class="fa fa-trash" aria-hidden="true"></i></a> '.$act;
	  
      if (isset($v['childs'])) 
		{
			$sub=getCatFullList($v['childs'],$parent,1,$sub);
		}
	  $sub.='</li>';
  }
  $sub.='</ul>';
  
  return $sub;
}