<?php
/*
 * *************************************************
 * Created on :2015-12-25 12:09:12
 * Encoding   :UTF-8
 * Description:
 *Usage: php DaemonCli.php 队列名(比如demo) start|stop
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */

require dirname(__FILE__) . '/../Core/Core.php';
require_once 'Core/Loader.php';
if (empty($argv[1])) {
    die("Usage: php " . __FILE__ . ' daemonName start|stop');
}
$daemonName = $argv[1];
$MaxProcess = isset($argv[3]) ? $argv[3] : 2;
$MaxRequestPerChild = isset($argv[4]) ? $argv[4] : 10;

$className = 'AT_Srv_Daemon_' . ucfirst($daemonName) . '_index';
AT_Srv_Loader::loadClass($className);
$daemon = new $className($daemonName, $MaxProcess, $MaxRequestPerChild);
if (!file_exists(SRV_DAEMON_FLAG_DIR) && !mkdir(SRV_DAEMON_FLAG_DIR)) {
    die("Make dir " . SRV_DAEMON_FLAG_DIR . ' fails');
}
switch ($argv[2]) {
    case 'stop':
        $daemon->stop();
        break;
    case 'start':
    default:
        $daemon->run();
        break;
}
/* End of file daemon */
