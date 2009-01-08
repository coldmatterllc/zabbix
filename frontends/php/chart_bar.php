<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
	require_once "include/config.inc.php";
	require_once "include/reports.inc.php";
	require_once "include/graphs.inc.php";
	require_once "include/classes/cbar.inc.php";
	
	$page["file"]	= "chart_bar.php";
	$page["title"]	= "S_CHART";
	$page["type"]	= PAGE_TYPE_IMAGE;

include_once "include/page_header.php";

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'config'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1,2,3'),		NULL),
		
		'hostids'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, 		null),
		'groupids'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, 		null),

		'items'=>			array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID,			NULL),

		'title'=>			array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'xlabel'=>			array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'ylabel'=>			array(T_ZBX_STR, O_OPT,  NULL,	null,		null),

		'showlegend'=>		array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'sorttype'=>		array(T_ZBX_INT, O_OPT,	null,	null,		null),
		
		'scaletype'=>		array(T_ZBX_INT, O_OPT,	NULL,	null,		NULL),
		'avgperiod'=>		array(T_ZBX_INT, O_OPT,	NULL,	null,		NULL),
		
		'periods'=>			array(T_ZBX_STR, O_OPT,	null,	null,		null),

		'report_timesince'=>	array(T_ZBX_INT, O_OPT,	P_UNSET_EMPTY,	null,	NULL),
		'report_timetill'=>		array(T_ZBX_INT, O_OPT,	P_UNSET_EMPTY,	null,	NULL),
	
	);

	check_fields($fields);
	
	$config = get_request('config',1);
	$title = get_request('title',S_REPORT);
	$xlabel = get_request('xlabel', S_X);
	$ylabel = get_request('ylabel', S_Y);
	
	$showlegend = get_request('showlegend', 0);
	$sorttype = get_request('sorttype', 0);
	
	if($config == 1){
		$items = get_request('items',array());
		$scaletype = get_request('scaletype',TIMEPERIOD_TYPE_WEEKLY);
		
		$timesince = get_request('report_timesince',time()-86400);
		$timetill = get_request('report_timetill',time());
		
		$str_since['hour'] = date('H',$timesince);
		$str_since['day'] = date('d',$timesince);
		$str_since['weekday'] = date('w',$timesince);
		if($str_since['weekday'] == 0) $str_since['weekday'] = 7;
		
		$str_since['mon'] = date('m',$timesince);
		$str_since['year'] = date('Y',$timesince);
		
		$str_till['hour'] = date('H',$timetill);
		$str_till['day'] = date('d',$timetill);
		$str_till['weekday'] = date('w',$timetill);
		if($str_till['weekday'] == 0) $str_till['weekday'] = 7;
		
		$str_till['mon'] = date('m',$timetill);
		$str_till['year'] = date('Y',$timetill);
		
		switch($scaletype){
			case TIMEPERIOD_TYPE_HOURLY: 
				$scaleperiod = 3600;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' '.$str_since['hour'].':00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' '.$str_till['hour'].':00:00';
				$timetill = strtotime($str) + $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_DAILY: 
				$scaleperiod = 86400;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' 00:00:00';
				$timetill = strtotime($str) + $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_WEEKLY: 
				$scaleperiod = 86400 * 7;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' 00:00:00';
				$timesince = strtotime($str);
				$timesince-= ($str_since['weekday']-1)*86400;

				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' 00:00:00';
				$timetill = strtotime($str);
				$timetill-= ($str_till['weekday']-1)*86400;

				$timetill+= $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_MONTHLY: 
				$scaleperiod = 86400 * 30;
				$str = $str_since['year'].'-'.$str_since['mon'].'-01 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-01 00:00:00';
				$timetill = strtotime($str);
				$timetill = strtotime('+1 month',$timetill);
				
				break;
			case TIMEPERIOD_TYPE_YEARLY: 
				$scaleperiod = 86400 * 365;
				$str = $str_since['year'].'-01-01 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-01-01 00:00:00';
				$timetill = strtotime($str);
				$timetill = strtotime('+1 year',$timetill);
				
				break;
		}
	
		$p = $timetill - $timesince;				// graph size in time
		$z = $p - ($timesince % $p);				// graphsize - mod(from_time,p) for Oracle...
		$x = round($p / $scaleperiod);				// graph size in px	
		$calc_field = 'round('.$x.'*(mod('.zbx_dbcast_2bigint('clock').'+'.$z.','.$p.'))/('.$p.'),0)';  /* required for 'group by' support of Oracle */
	
		$period_step = $scaleperiod;	
		
		$graph = new CBar(GRAPH_TYPE_COLUMN);
//		$graph = new CBar(GRAPH_TYPE_BAR);
		$graph->setHeader($title);
		
		$graph_data['colors'] = array();
		$graph_data['legend'] = array();
		$db_values = array();
		foreach($items as $num => $item){
			$itemid = $item['itemid'];
			$item_data = &$db_values[$itemid];
			
			$graph_data['legend'][] = $item['caption'];
			
			$sql_arr = array();
			array_push($sql_arr,
				'SELECT itemid,'.$calc_field.' as i,'.
					' sum(num) as count,avg(value_avg) as avg,min(value_min) as min,'.
					' max(value_max) as max,max(clock) as clock'.
				' FROM trends '.
				' WHERE itemid='.$itemid.
					' AND clock>='.$timesince.
					' AND clock<='.$timetill.
				' GROUP BY itemid,'.$calc_field.
				' ORDER BY clock ASC'
				,
	
				'SELECT itemid,'.$calc_field.' as i,'.
					' sum(num) as count,avg(value_avg) as avg,min(value_min) as min,'.
					' max(value_max) as max,max(clock) as clock'.
				' FROM trends_uint '.
				' WHERE itemid='.$itemid.
					' AND clock>='.$timesince.
					' AND clock<='.$timetill.
				' GROUP BY itemid,'.$calc_field.
				' ORDER BY clock ASC'
				);
				
	
			foreach($sql_arr as $id => $sql){
				$result=DBselect($sql);
//SDI($sql);
				$i = 0;
				$start = 0;
				$end = $timesince;		
				while($end < $timetill){
					switch($scaletype){
						case TIMEPERIOD_TYPE_HOURLY: 
						case TIMEPERIOD_TYPE_DAILY: 
						case TIMEPERIOD_TYPE_WEEKLY: 
							$start = $end;
							$end = $start + $scaleperiod;
							break;
						case TIMEPERIOD_TYPE_MONTHLY: 
							$start = $end;
							
							$str_start['mon'] = date('m',$start);
							$str_start['year'] = date('Y',$start);
				
							$str = $str_start['year'].'-'.$str_start['mon'].'-01 00:00:00';
							$end = strtotime($str);
							$end = strtotime('+1 month',$end);
							break;
						case TIMEPERIOD_TYPE_YEARLY: 
							$start = $end;
							
							$str_start['year'] = date('Y',$start);
				
							$str = $str_start['year'].'-01-01 00:00:00';
							$end = strtotime($str);
							$end = strtotime('+1 year',$end);
							break;
					}

					if(!isset($row) || ($row['clock']<$start)){
						$row=DBfetch($result);
//SDI($row['clock']);
					}
										
//SDI($start.' < '.$row['clock'].' < '.$end);
					if(isset($row) && $row && ($row['clock']>=$start) && (($row['clock']<$end))){
						$item_data['count'][$i]	= $row['count'];
						$item_data['min'][$i]		= $row['min'];
						$item_data['avg'][$i]		= $row['avg'];
						$item_data['max'][$i]		= $row['max'];
						$item_data['clock'][$i]		= $start;
						$item_data['type'][$i]		= true;
					}
					else{
						if(isset($item_data['type'][$i]) && $item_data['type'][$i]) continue;
						
						$item_data['count'][$i]	= 0;
						$item_data['min'][$i]	= 0;
						$item_data['avg'][$i]	= 0;
						$item_data['max'][$i]	= 0;
						$item_data['clock'][$i]	= $start;
						$item_data['type'][$i]	= false;
					}
					$i++;
				}
				unset($row);
			}
	
			switch($item['calc_fnc']){
				case 0:
					$graph->addSeries($item_data['count']);
					break;
				case CALC_FNC_MIN:
					$graph->addSeries($item_data['min']);
					break;
				case CALC_FNC_AVG:
					$graph->addSeries($item_data['avg']);
					break;
				case CALC_FNC_MAX:
					$graph->addSeries($item_data['max']);
					break;
			}
			
			$graph_data['colors'][] = $item['color'];
			
			if(!isset($graph_data['captions'])){
				$graph_data['captions'] = array();
				foreach($item_data['clock'] as $id => $clock){
					$graph_data['captions'][$id] = date('Y.m.d',$clock);
				}
			}
		}
	}
	else if($config == 2){
		$items = get_request('items',array());
		$periods = get_request('periods',array());
	
		$graph = new CBar(GRAPH_TYPE_COLUMN);
	//	$graph = new CBar(GRAPH_TYPE_BAR);
		$graph->setHeader('REPORT 1');
		
		$graph_data = array();
		
		$graph_data['colors'] = array();
		$graph_data['captions'] = array();
		$graph_data['values'] = array();
		$graph_data['legend'] = array();
		
		foreach($periods as $pid => $period){
			$graph_data['colors'][] = $period['color'];
			$graph_data['legend'][] = $period['caption'];
			
			$db_values[$pid] = array();
			foreach($items as $num => $item){
				$itemid = $item['itemid'];
				$item_data = &$db_values[$pid][$itemid];
				
				$sql = 'SELECT itemid, sum(num) as count,avg(value_avg) as avg,min(value_min) as min,'.
						' max(value_max) as max,max(clock) as clock'.
					' FROM trends '.
					' WHERE itemid='.$itemid.
						' AND clock>='.$period['report_timesince'].
						' AND clock<='.$period['report_timetill'].
					' GROUP BY itemid';
				$result=DBselect($sql);
				if($row=DBfetch($result)){
					$item_data['count']	= $row['count'];
					$item_data['min']	= $row['min'];
					$item_data['avg']	= $row['avg'];
					$item_data['max']	= $row['max'];
					$item_data['clock']	= $row['clock'];
				}
		
				$sql = 'SELECT itemid, sum(num) as count,avg(value_avg) as avg,min(value_min) as min,'.
						' max(value_max) as max,max(clock) as clock'.
					' FROM trends_uint '.
					' WHERE itemid='.$itemid.
						' AND clock>='.$period['report_timesince'].
						' AND clock<='.$period['report_timetill'].
					' GROUP BY itemid';
				$result=DBselect($sql);
				if($row=DBfetch($result)){
					if(!empty($item_data)){
						$item_data['count']	+= $row['count'];
						$item_data['min']	= min($item_data['count'],$row['min']);
						$item_data['avg']	= ($item_data['count']+$row['avg'])/2;
						$item_data['max']	= max($item_data['count'],$row['max']);
						$item_data['clock']	= max($item_data['count'],$row['clock']);
					}
					else{
//SDI($row);
						$item_data['count']	= $row['count'];
						$item_data['min']	= $row['min'];
						$item_data['avg']	= $row['avg'];
						$item_data['max']	= $row['max'];
						$item_data['clock']	= $row['clock'];
					}
				}

// fixes bug #21788, due to Zend casting the array key as a numeric and then they are reassigned
				$itemid = "0$itemid";
//---
				switch($item['calc_fnc']){
					case 0:
						$graph_data['values'][$itemid] = $item_data['count'];
						break;
					case CALC_FNC_MIN:
						$graph_data['values'][$itemid] = $item_data['min'];
						break;
					case CALC_FNC_AVG:
						$graph_data['values'][$itemid] = $item_data['avg'];
						break;
					case CALC_FNC_MAX:
						$graph_data['values'][$itemid] = $item_data['max'];
						break;
				}
				
				$graph_data['captions'][$itemid] = $item['caption'];
			}
	
			if(($sorttype == 0) || (count($periods) < 2))
				array_multisort($graph_data['captions'], $graph_data['values']);
			else
				array_multisort($graph_data['values'], SORT_DESC, $graph_data['captions']);

			$graph->addSeries($graph_data['values']);
		}
	}
	else if($config == 3){

		$items = get_request('items',array());
		$hostids = get_request('hostids',array());
		$groupids = get_request('groupids',array());
				
		$title = get_request('title','Report 2');	
		$xlabel = get_request('xlabel','');
		$ylabel = get_request('ylabel','');
		
		
		$scaletype = get_request('scaletype', TIMEPERIOD_TYPE_WEEKLY);
		$avgperiod = get_request('avgperiod', TIMEPERIOD_TYPE_DAILY);
		
		if(!empty($groupids)){
			$sql = 'SELECT DISTINCT hg.hostid '.
				' FROM hosts_groups hg '.
				' WHERE '.DBcondition('hg.groupid', $groupids);
			$res = DBselect($sql);
			while($db_host = DBfetch($res)){
				$hostids[$db_host['hostid']] = $db_host['hostid'];
			}
		}

		$itemids = array();
		foreach($items as $num => $item){
			if($item['itemid'] > 0){
				$itemids = get_same_item_for_host($item['itemid'],$hostids);
				break;
			}
		}

		$graph = new CBar(GRAPH_TYPE_COLUMN);
//		$graph = new CBar(GRAPH_TYPE_BAR);
		$graph->setHeader('REPORT 3');
		
		$graph_data = array();
		
		$graph_data['colors'] = array();
		$graph_data['captions'] = array();
		$graph_data['values'] = array();
		$graph_data['legend'] = array();
		

		$timesince = get_request('report_timesince',time()-86400);
		$timetill = get_request('report_timetill',time());
		
//SDI(date('Y.m.d H:i:s',$timesince).' - '.date('Y.m.d H:i:s',$timetill));
		$str_since['hour'] = date('H',$timesince);
		$str_since['day'] = date('d',$timesince);
		$str_since['weekday'] = date('w',$timesince);
		if($str_since['weekday'] == 0) $str_since['weekday'] = 7;
		
		$str_since['mon'] = date('m',$timesince);
		$str_since['year'] = date('Y',$timesince);
		
		$str_till['hour'] = date('H',$timetill);
		$str_till['day'] = date('d',$timetill);
		$str_till['weekday'] = date('w',$timetill);
		if($str_till['weekday'] == 0) $str_till['weekday'] = 7;
		
		$str_till['mon'] = date('m',$timetill);
		$str_till['year'] = date('Y',$timetill);
		
		switch($scaletype){
			case TIMEPERIOD_TYPE_HOURLY: 
				$scaleperiod = 3600;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' '.$str_since['hour'].':00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' '.$str_till['hour'].':00:00';
				$timetill = strtotime($str) + $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_DAILY: 
				$scaleperiod = 86400;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' 00:00:00';
				$timetill = strtotime($str) + $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_WEEKLY: 
				$scaleperiod = 86400 * 7;
				$str = $str_since['year'].'-'.$str_since['mon'].'-'.$str_since['day'].' 00:00:00';
				$timesince = strtotime($str);
				$timesince-= ($str_since['weekday']-1)*86400;

				$str = $str_till['year'].'-'.$str_till['mon'].'-'.$str_till['day'].' 00:00:00';
				$timetill = strtotime($str);
				$timetill-= ($str_till['weekday']-1)*86400;

				$timetill+= $scaleperiod;
				
				break;
			case TIMEPERIOD_TYPE_MONTHLY: 
				$scaleperiod = 86400 * 30;
				$str = $str_since['year'].'-'.$str_since['mon'].'-01 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-'.$str_till['mon'].'-01 00:00:00';
				$timetill = strtotime($str);
				$timetill = strtotime('+1 month',$timetill);
				
				break;
			case TIMEPERIOD_TYPE_YEARLY: 
				$scaleperiod = 86400 * 365;
				$str = $str_since['year'].'-01-01 00:00:00';
				$timesince = strtotime($str);
				
				$str = $str_till['year'].'-01-01 00:00:00';
				$timetill = strtotime($str);
				$timetill = strtotime('+1 year',$timetill);
				
				break;
		}
// updating 
					
//SDI(date('Y.m.d H:i:s',$timesince).' - '.date('Y.m.d H:i:s',$timetill));

		switch($avgperiod){
			case TIMEPERIOD_TYPE_HOURLY: 
				$period = 3600;
				break;
			case TIMEPERIOD_TYPE_DAILY: 
				$period = 86400;
				break;
			case TIMEPERIOD_TYPE_WEEKLY: 
				$period = 86400 * 7;
				break;
			case TIMEPERIOD_TYPE_MONTHLY: 
				$period = 86400 * 30;
				break;
			case TIMEPERIOD_TYPE_YEARLY: 
				$period = 86400 * 365;				
				break;
		}
		
		$hosts = get_host_by_itemid($itemids);

		$db_values = array();
		foreach($itemids as $num => $itemid){
			$count = 0;
			if(!isset($db_values[$count])) $db_values[$count] = array();
			$graph_data['captions'][$itemid] = $hosts[$itemid]['host'];

			$start = 0;
			$end = $timesince;			
			while($end < $timetill){
				switch($scaletype){
					case TIMEPERIOD_TYPE_HOURLY: 
					case TIMEPERIOD_TYPE_DAILY: 
					case TIMEPERIOD_TYPE_WEEKLY: 
						$start = $end;
						$end = $start + $scaleperiod;
						break;
					case TIMEPERIOD_TYPE_MONTHLY: 
						$start = $end;
						
						$str_start['mon'] = date('m',$start);
						$str_start['year'] = date('Y',$start);
			
						$str = $str_start['year'].'-'.$str_start['mon'].'-01 00:00:00';
						$end = strtotime($str);
						$end = strtotime('+1 month',$end);
						break;
					case TIMEPERIOD_TYPE_YEARLY: 
						$start = $end;
						
						$str_start['year'] = date('Y',$start);
			
						$str = $str_start['year'].'-01-01 00:00:00';
						$end = strtotime($str);
						$end = strtotime('+1 year',$end);
						break;
				}
				
				$p = $end - $start;						// graph size in time
				$z = $p - ($start % $p);				// graphsize - mod(from_time,p) for Oracle...
				$x = floor($scaleperiod / $period);		// graph size in px	
				$calc_field = 'round('.$x.'*(mod('.zbx_dbcast_2bigint('clock').'+'.$z.','.$p.'))/('.$p.'),0)';  /* required for 'group by' support of Oracle */
				
				$item_data = null;
				
				$sql_arr = array();
				
				array_push($sql_arr,
					'SELECT itemid,'.$calc_field.' as i,sum(num) as count,avg(value_avg) as avg '.
					' FROM trends '.
					' WHERE itemid='.$itemid.
						' AND clock>='.$start.
						' AND clock<='.$end.
					' GROUP BY itemid,'.$calc_field
					,
		
					'SELECT itemid,'.$calc_field.' as i,sum(num) as count,avg(value_avg) as avg '.
					' FROM trends_uint '.
					' WHERE itemid='.$itemid.
						' AND clock>='.$start.
						' AND clock<='.$end.
					' GROUP BY itemid,'.$calc_field
					);
					
				foreach($sql_arr as $id => $sql){
					$result=DBselect($sql);
					while($row=DBfetch($result)){
						if($row['i'] == $x) continue;
						if(!is_null($item_data))
							$item_data = ($item_data + $row['avg']) / 2;
						else
							$item_data = $row['avg'];
					}

				}
//SDI($count.' : '.$itemid);
				$db_values[$count][$itemid] = is_null($item_data)?0:$item_data;

				$tmp_color = get_next_color(true);

				if(!isset($graph_data['colors'][$count]))
					$graph_data['colors'][$count] = rgb2hex($tmp_color);
				$graph_data['legend'][$count] = date('Y.m.d', $start);
				
				$count++;
			}
		}
		foreach($db_values as $num => $item_data){
			$graph->addSeries($item_data);
		}
//----
	}
	
	$graph->setSeriesLegend($graph_data['legend']);
	$graph->setPeriodCaption($graph_data['captions']);
	
	$graph->setHeader($title);
	$graph->setXLabel($xlabel);
	$graph->setYLabel($ylabel);

	$graph->setSeriesColor($graph_data['colors']);

	$graph->showLegend($showlegend);

	$graph->setWidth(1024);
	$graph->setHeight(400);
	
	$graph->Draw();	
?>
<?php
include_once "include/page_footer.php";
?>