<?php 
/*
1.查找某个字段在数据库的哪个表中
2.查找数据库某内容在哪个表哪个字段
3.后台操作反应到数据库的记录变化
*/

$sesspath = './session';
$sid = 'qeo1nga5uu4i0p3r943a30r2u5';
define('NOTICELEVEL', 20);
define('WARNINGLEVEL',10);
define('LOGLEVEL', WARNINGLEVEL);
define('GROUPSEPARATOR', '->UNIQSP1-<');// GROUP_CONCAT 字段链接用
define('FIELDSEPARATOR', '->UNIQSP2<-');// 字段和hash值链接用
define('NOCACHE', 0);
$sidfile = rtrim($sesspath, '/') . "/sess_{$sid}";

!is_dir($sesspath) && mkdir($sesspath);
!file_exists($sidfile) && touch($sidfile);

session_save_path($sesspath);
session_id($sid);
session_start();

$a = isset($_GET['a']) ? $_GET['a'] : 'beforeChange';
$wd = isset($_GET['wd']) ? $_GET['wd'] : 'zc.qq.com';

if (empty($wd)) exit('empty wd.');

header('content-type:text/html;charset=utf-8');
echo '<pre>';

include 'zhuanpan/public/inc/Mysql.class.php';
$dbhost = '127.0.0.1';
$dbuser = 'root';
$dbpwd = '123456';
$dbname = 'cmb_insurance';

$db = new Mysql(array(
	'dbhost' => $dbhost,
	'dbuser' => $dbuser,
	'dbpwd'  => $dbpwd,
	'dbname' => $dbname,
	'dbport' => '',
	'pre'    => ''
));

set_time_limit(0);

$tables = $db->getArr("show tables from {$dbname}", true);

switch ($a) {
	// 查找字段在哪个表
	case 'findf':
		foreach ($tables as $table) {
			$fields = $db->getArr("show fields from {$table}", true);
			if(in_array($wd, $fields)){
				echo 'table:', $table,'<br>';
			}
		}
	break;
	// 查找指定内容在哪个表哪个字段
	case 'findc':
		foreach ($tables as $table) {
			$fields = $db->getArr("show fields from {$table}", true);
			foreach ($fields as $field) {
				$row = $db->getAssoc($table, "`{$field}` like '%{$wd}%'");
				if(!empty($row)){
					echo 'table:',$table,'|field:',$field,'<br>';
					print_r($row);
					echo '<hr>';
					// exit;
				}
				// echo $db->lastsql;exit;
			}
		}
	break;
	// 后台操作反应到数据库的记录变化，反应后台insert操作
	case 'beforeChange':
		!isset($_SESSION[$dbname]) && $_SESSION[$dbname] = array();

		$_SESSION[$dbname] = buildTableHashMap($tables);

		print_r($_SESSION[$dbname]);
	break;
	// 后台操作完后访问，显示数据库变化
	case 'showChange':
		if(empty($_SESSION[$dbname])){
			exit('操作顺序有误，请按照：a=beforeChange => 后台操作 => a=showChange操作');
		}
		// 后台操作前的表hash值
		$beforeInfo = $_SESSION[$dbname];
		// 后台操作后的表hash值,有缓存
		$temp = $dbname . '_new';
		$newInfo = isset($_SESSION[$temp]) ? $_SESSION[$temp] : '';
		if(empty($newInfo) || NOCACHE) {
			$newInfo = $_SESSION[$temp] = buildTableHashMap($tables);
		}
		

		// 展示表数量变化
		$beforeTables = array_keys($beforeInfo);
		$newTables = array_keys($newInfo);
		$tablesDiff = getArrDiff($beforeTables, $newTables);
		// 后台操作后有表被删除
		if(!empty($tablesDiff['diff1'])){
			echo 'table droped after change:',implode(',', $tablesDiff['diff1']),'<br>';
		}
		// 后台操作有表新增
		if(!empty($tablesDiff['diff2'])){
			echo 'table added after change:',implode(',', $tablesDiff['diff2']),'<br>';
		}

		// 展示表内具体变化
		// todo .. 是否可以在buildTableHashMap中操作?
		echo 'in loop<br>';
		while (list($table, $oldRowInfo) = each($beforeInfo)) {
			if(!isset($newInfo[$table])){
				echo 'table droped:',$table,'<br>';
				continue;
			}
			echo $table,'<br>';

			$tableToCompare = $newInfo[$table];

			if($oldRowInfo['rownum'] == $tableToCompare['rownum']){
				if(empty($oldRowInfo['md5'])) $oldRowInfo['md5'] = array();
				if(empty($tableToCompare['md5'])) $tableToCompare['md5'] = array();
				$tablesDiff = getArrDiff($oldRowInfo['md5'], $tableToCompare['md5']);
				
				// 删除了数据
				if(!empty($tablesDiff['diff1'])){
					echo 'diff1<br>';
					print_r($tablesDiff['diff1']);
					exit;
				}
				// 新增了数据
				if(!empty($tablesDiff['diff2'])){
					echo 'diff2<br>';
					print_r($tablesDiff['diff2']);
					exit;
				}
			}
			// print_r($oldRowInfo);
			// print_r($tableToCompare);
			// exit;
			
			// print_r($tableToCompare);
			// exit;
			// echo $table;
		}

		print_r($beforeTables);
		echo '<hr>';
		print_r($newTables);
		exit;
		// 比对表hash变化
		print_r($newInfo);



	break;
	
	default:
		break;
}

/**
 * 获取2个数组值不同的地方
 * @param  array $arr1 
 * @param  array $arr2 
 * @return array diff1 => array(arr2比arr1少的数据) diff2 => array(arr1比arr2少的部分)
 */
function getArrDiff($arr1, $arr2){
	$ret = array();

	$commonValues = array_intersect($arr1, $arr2);
	$commonValuesCount = count($commonValues);
	// 如果有交集的表的数量和arr1数量不同，那么获取arr2比arr1缺少的部分。
	if($commonValuesCount !== count($arr1)){
		$ret['diff1'] = array_diff($arr1, $commonValues);
	}
	// 如果有交集的表的数量和arr2数量不同，那么获取arr1比arr2缺少的部分。
	if($commonValuesCount !== count($arr2)){
		$ret['diff2'] = array_diff($arr2, $commonValues);
	}

	return $ret;
}

/**
 * 获取数组第一个值
 * @param  array $arr 
 * @return       
 */
function getFirstElem($arr){
	return array_shift($arr);
}


/**
 * 生成数据表的hash信息
 * @param  array $tables 数据库表
 * @return array array(表名=>array(rownum=>行数,lastid=>10086,md5=>array(primaryKey => 该行的md5值)))
 * todo .. 没主键可能会出现后面那条记录的hash值覆盖前面那条
 */
function buildTableHashMap($tables){
	global $db,$dbname;

	$ret = array();

	// 获取数据库表的主键,有复合主键,用于每个表每一行的hash数组键
	// todo .. GROUP_CONCAT默认用,链接，如果值里也有,需调整
	$sql = "SELECT GROUP_CONCAT(t1.COLUMN_NAME SEPARATOR '".GROUPSEPARATOR."') `fields`,t2.table_name tbname 
	FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS t2 
	JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE t1 ON t2.table_name = t1.table_name
	WHERE t2.CONSTRAINT_TYPE = 'PRIMARY KEY' AND t2.TABLE_SCHEMA = '{$dbname}'
	GROUP BY t1.table_name";

	$temp = $db->getArr($sql);
	$primaryFields = array();
	// 表名=>主键名
	while (list($k, $arr) = each($temp)) {
		$primaryFields[$arr['tbname']] = $arr['fields'];
	}
	// print_r($primaryFields);
	// exit;

	$primaryIds = array('id', 'entity_id');

	foreach ($tables as $table) {
		$record = array();
		$primaryid = '';

		$fields = $db->getArr("show fields from {$table}", true);

		$record['rownum'] = $db->getRowNum($table);
		// 如果这个表的数据量小于指定值 那么每一条记录进行hash
		if($record['rownum'] < 10000){
			$allrow = $db->getArr("select * from {$table}");
			if(empty($allrow)){
				if(LOGLEVEL >= NOTICELEVEL)
					echo '[notice] pass building row hashes on empty table: [',$table,']<br>';
				continue;
			}
			// 建立每一行的hash
			$rowi = 0;
			while(list($k, $arr) = each($allrow)){
				++$rowi;
				$hash = md5(implode('', array_values($arr)));
				// 如果该表有主键就用主键
				// todo .. 没主键用唯一键，最后再用第一个字段值
				// 用主键值作为该行的数组键
				$hashKey = isset($primaryFields[$table]) ? $primaryFields[$table] : getFirstElem($fields);

				$prefix = '';
				foreach (explode(GROUPSEPARATOR, $hashKey) as $field) {
					// todo .. 如果值有空格什么的 或者其他特殊值
					$prefix .= $arr[$field];
				}
				if(empty($prefix)){
					if(LOGLEVEL >= WARNINGLEVEL)
						echo '[warning] empty hashkey from table:',$table,'|',$hashKey,' ----in row ',$rowi,'<hr>';
					continue;
				}

				$record['md5'][] = $prefix . FIELDSEPARATOR .$hash;
			}
		}else{
			// 否则使用预设的自增id类型字段最后一条最为判断依据
			foreach ($primaryIds as $id) {
				if(in_array($id, $fields)){
					$primaryid = $id;
					break;
				}
			}
			if($primaryid){
				// 最后一条记录的id记下来
				$lastid = $db->getOneField($table, $primaryid, "1=1 order by {$primaryid} desc limit 1");
				$record['lastid'] = $lastid;
			}else{
				if(LOGLEVEL >= WARNINGLEVEL)
					echo '[warning] unwatched table:',$table,'<br>';
			}
		}

		$ret[$table] = $record;
	}

	return $ret;
}


