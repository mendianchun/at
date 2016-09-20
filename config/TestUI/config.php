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
        'dbname' => 'at',
        'dbcharset' => 'UTF8'
    ),
    'case_dir' => dirname(__FILE__) . '/../../case/testUI/',
    'db_file' => dirname(__FILE__) . '/../../data/testUI/testcase.sql',
    'mail_to' => 'mendianchun@acttao.com;yanzhangqian@acttao.com;chenyawei@acttao.com',
    'branch' => 'damen',
    'rev_mobile' => '15XXXXXXXXX',
    'function_one' => 1
);
