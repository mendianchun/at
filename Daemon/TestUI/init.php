<?php
/*
 * *************************************************
 * Created on :2015-12-26 10:18:12
 * Encoding   :UTF-8
 * Description:初始化需要测试的接口队列，同时创建测试数据库.
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */
error_reporting(0);
require dirname(__FILE__) . '/../../vendor/autoload.php';
require dirname(__FILE__) . '/../../vendor/predis/predis/src/Autoloader.php';
require dirname(__FILE__) . '/testcase.php';
require dirname(__FILE__) . '/config/config.php';
require dirname(__FILE__) . '/../Class/db.class.php';

Predis\Autoloader::register();

$redis = new Predis\Client ($_config['redis_server']);

//读取case目录的文件，将测试用例写到队列。
$fileArray = scandir($_config['case_dir']);
foreach ($fileArray as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }
    $count = 0;
    $testcase = new testcase($file);
    $case_data = $testcase->getdata();
    //插入队列
    if (is_array($case_data)) {
        foreach ($case_data as $v) {
//            echo json_encode($v)."\n";
            $redis->rpush($_config['queue_name'], json_encode($v)) . "\n";
            $count++;
        }
    } else {
        $redis->rpush($_config['queue_name'], json_encode($case_data)) . "\n";
        $count = 1;
    }
    if ($count > 0) {
        list($ui,$suffix) = explode(".",$file);
        echo "测试用例  " . $ui . " ... 初始化成功，共" . $count . "个\n";
    }
}
echo "初始化测试用例 ... 成功\n";

echo "...............................................................\n";
//初始化数据
$db = new db;
$db->connect($_config['mysql_server']['host'] . ":" . $_config['mysql_server']['port'], $_config['mysql_server']['user'], $_config['mysql_server']['password'], $_config['mysql_server']['dbname'], $_config['mysql_server']['dbcharset']);

$sql = file_get_contents($_config['db_file']);
$sql = str_replace("\r\n", "\n", $sql);
runquery($sql, $db);
echo "初始化数据 ... 成功\n";

/**
 * drop table
 */
function droptable($table_name)
{
    return "DROP TABLE IF EXISTS `" . $table_name . "`;";
}

//execute sql
function runquery($sql, $db)
{
    if (!isset($sql) || empty($sql)) return;

    $ret = array();
    $num = 0;
    foreach (explode(";\n", trim($sql)) as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        foreach ($queries as $query) {
            $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
        }
        $num++;
    }
    unset($sql);
    foreach ($ret as $query) {
        $query = trim($query);
        if ($query) {
            if (substr($query, 0, 12) == 'CREATE TABLE') {
                $line = explode('`', $query);
                $data_name = $line[1];
                echo "数据表  " . $data_name . " ... 创建成功\n";
                $db->query(droptable($data_name));
                $db->query($query);
                unset($line, $data_name);
            } else {
                $db->query($query);
            }
        }
    }
}