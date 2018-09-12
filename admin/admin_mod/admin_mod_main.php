<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied");
$content_mod .= '
<div class="row">';

// ===== Быстрый доступ =====
//какие действия добавления не показывать
$array_actions_exceptions = array(5);
$buttons_colors_rotation = array('primary','success','info','danger','warning');
$content_mod .= '
  <div class="col-md-12">
    <div class="box box-danger">
      <div class="box-header">
        <h3 class="box-title">Быстрый доступ</h3>
      </div>
      <div class="box-body">              
        <div class="row dashboard_action_buttons">';
$btn_color_counter = 0;
foreach ($array_mod as $v) {
  $curr_btn_icon = $v['img'];
  foreach ($v['pages'] as $curr_action_id=>$curr_action_fields) {
    if ( (substr_count($curr_action_fields[0],'edit')>0)&&(!in_array($curr_action_id,$array_actions_exceptions)) ) {
      if ($btn_color_counter==count($buttons_colors_rotation)) $btn_color_counter=0;
      $curr_btn_title = $curr_action_fields[1];
      $content_mod .= '
          <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
            <a class="btn btn-'.$buttons_colors_rotation[$btn_color_counter].' btn-block btn-social" href="index.php?mod='.$v['name_mod'].'&action='.$curr_action_fields[0].'">
              <i class="fa fa-'.$curr_btn_icon.'"></i>
              '.$curr_btn_title.'
            </a>
          </div>';
      $btn_color_counter++;
    }
  }
}
$content_mod .= '
        </div>
      </div><!-- /.box-body -->
    </div>
  </div>';
// ===== /Быстрый доступ =====

$content_mod .= '
  <div class="col-md-12">
    <div class="row">
      <div class="col-md-6">
        <div class="row">';
// ===== Статистика системы =====
$content_mod .= '
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-header">
                <h3 class="box-title">Статистика системы за последние 10 дней</h3>
              </div>
              <div class="box-body">
                <div class="row">';
$content_mod .= '
                  <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="row">';

$Db->query = "SELECT COUNT(`id`) as `count`,`status` FROM `mod_notifications` WHERE TO_DAYS(NOW()) - TO_DAYS(date) <= 9 GROUP BY `status` ORDER BY `status`";
$Db->query();
if (mysql_num_rows($Db->lQueryResult)>0) {
  $statistics = array();
	while($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
    $statistics[$lRes['status']] = $lRes['count'];
	}
}

for ($i=1;$i<=3;$i++){
  switch ($i) {
    case '1': 
      $count = $statistics[1]+$statistics[2]+$statistics[3]; 
      $current_status = 'обработано'; 
      $current_color = 'bg-aqua'; 
      $current_icon = 'fa-binoculars'; 
      break;
    case '2': 
      $count = $statistics[2]+$statistics[3]; 
      $current_status = 'переговоров'; 
      $current_color = 'bg-yellow'; 
      $current_icon = 'fa-users'; 
      break;
    case '3': 
      $count = $statistics[3]; 
      $current_status = 'сделок'; 
      $current_color = 'bg-green'; 
      $current_icon = 'fa-check-square-o'; 
      break;
  }
  $content_mod .= '
                      <div class="col-lg-12 col-md-6">
                        <div class="small-box '.$current_color.'">
                          <div class="inner">
                            <h3>'.(int)$count.'</h3>
                            <p>'.$current_status.'</p>
                          </div>
                          <div class="icon">
                            <i class="fa '.$current_icon.'"></i>
                          </div>
                        </div>
                      </div>';
}

$include_morrisjs = true;
$Db->query = "SELECT COUNT(`id`) as `count`,`date` FROM `mod_notifications` WHERE TO_DAYS(NOW()) - TO_DAYS(date) <= 9 GROUP BY `date` ORDER BY `date`";
$Db->query();
if (mysql_num_rows($Db->lQueryResult)>0) {
  $last_10_day_notices = array();
	while($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
		(int)$last_10_day_notices[date('d-m-Y',strtotime($lRes['date']))]+=(int)$lRes['count'];
	}
}

for ($day_minus=9;$day_minus>=0;$day_minus--) {
  $morris_graph_data .= '
            {label: \''.formatedDate(date('d-m-Y',strtotime('-'.$day_minus.' day')),false,false).'\', count: '.(int)$last_10_day_notices[date('d-m-Y',strtotime('-'.$day_minus.' day'))].' },';
}
$morris_graph_data = clearLastComma($morris_graph_data);

$content_mod .= '
                    </div>
                  </div>
                  
                  <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="chart" id="line-chart" style="height: 300px;"></div>
                    <a href="index.php?mod=notifications&action=list" class="btn btn-success btn-block block-center">Подробнее</a>
                  </div>';

$content_mod .= '
                </div>
              </div><!-- /.box-body -->
            </div>
          </div>';
// ===== /Статистика системы =====

/*
// ===== Посещаемость =====
$content_mod .= '
          <div class="col-md-12">
            <div class="box box-danger">
              <div class="box-header">
                <h3 class="box-title">Посещаемость</h3>
              </div>
              <div class="box-body">';
$content_mod .= 'Тут однажды будет статистика из Я.Метрики';
$content_mod .= '
              </div><!-- /.box-body -->
            </div>
          </div>';
// ===== /Посещаемость =====
*/


$content_mod .= '
        </div>
      </div>';

// ===== Лента обновлений =====
$content_mod .= '
      <div class="col-md-6">
        <h3 class="box-title timeline-title">Лента обновлений</h3>';
$Db->query = "SELECT * FROM `mod_stat` ORDER BY `date` DESC LIMIT 25";
$Db->query();
if (mysql_num_rows($Db->lQueryResult)>0) {
  $timeline_arr = array();
	while($lRes = mysql_fetch_assoc($Db->lQueryResult)) {
		$timeline_arr[date('d.m.Y',strtotime($lRes['date']))][] = array(
      'time'=>date('H:i',strtotime($lRes['date'])),
      'type'=>$lRes['type'],
      'text'=>$lRes['text']
    );
	}
}
if (count($timeline_arr)>0) {
  $content_mod .= '
<ul class="timeline">';
  foreach ($timeline_arr as $timeline_day=>$timeline_day_events) {
    $content_mod .= '
  <!-- timeline time label -->
  <li class="time-label">
      <span class="bg-red">
          '.formatedDate($timeline_day,false,true,false).'
      </span>
  </li>
  <!-- /.timeline-label -->';
    foreach ($timeline_day_events as $timeline_event) {
      $event_icons = array(
        1=>'fa-user bg-aqua',
        2=>'fa-plus bg-green',
        3=>'fa-envelope bg-blue',
        4=>'fa-comments bg-yellow'
      );
      $content_mod .= '
  <!-- timeline item -->
  <li>
      <!-- timeline icon -->
      <i class="fa '.$event_icons[$timeline_event['type']].'"></i>
      <div class="timeline-item">
        <span class="time"><i class="fa fa-clock-o"></i> '.$timeline_event['time'].'</span>

        <h3 class="timeline-header">'.$timeline_event['text'].'</h3>';
        /*<!--
        <div class="timeline-body">
            ...
            Content goes here
        </div>

        <div class="timeline-footer">
            <a class="btn btn-primary btn-xs">...</a>
        </div>
        -->*/
      $content_mod .= '
      </div>
  </li>
  <!-- END timeline item -->';
    }
  }
  $content_mod .= '
  <li>
    <i class="fa fa-clock-o bg-gray"></i>
  </li>
</ul>';
}
$content_mod .= '
        </div>';
// ===== /Лента обновлений =====
$content_mod .= '
      </div>
    </div>
  </div>';