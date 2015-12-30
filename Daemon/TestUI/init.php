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

Predis\Autoloader::register();

$redis = new Predis\Client ($_config['redis_server']);

//读取case目录的文件，将测试用例写到队列。

$fileArray = scandir($_config['case_dir']);
foreach ($fileArray as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }

    $testcase = new testcase($file);
    $case_data = $testcase->getdata();
    //插入队列
    if (is_array($case_data)) {
        foreach ($case_data as $v) {
//            echo json_encode($v)."\n";
            echo $redis->rpush($_config['queue_name'], json_encode($v)) . "\n";
        }
    } else {
        echo $redis->rpush($_config['queue_name'], json_encode($case_data)) . "\n";
    }
}

//初始化数据库

