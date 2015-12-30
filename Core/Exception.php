<?php

/*
 * *************************************************
 * Created on :2015-12-25 12:00:12
 * Encoding   :UTF-8
 * Description:
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */

class AT_Srv_Exception extends Exception
{
    public function __construct($message, $code = 0) {
        // make sure everything is assigned properly
        parent::__construct($message, $code);
        //@todo log to syslogng or scribe
    }
}

/* End of file Exception */