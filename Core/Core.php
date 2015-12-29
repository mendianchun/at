<?php

/*
 * *************************************************
 * Created on :2015-12-25 11:57:12
 * Encoding   :UTF-8
 * Description:
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */
define('AT_SRV_INC_DIR', realpath(dirname(__FILE__) . '/../'));
//设置include路径
ini_set('include_path', AT_SRV_INC_DIR . ':' . ini_get('include_path') );
/* End of file Core */