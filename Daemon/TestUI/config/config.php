<?php
/*
 * *************************************************
 * Created on :2015-12-29 17:32:12
 * Encoding   :UTF-8
 * Description:
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */

$_config = array(
    'redis_server' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ),
    'queue_name' => 'testCaseList',
    'mysql_server' => array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => 'root',
        'dbname' => 'at'
    ),
    'case_dir' => dirname(__FILE__) . '/../case/'
);


