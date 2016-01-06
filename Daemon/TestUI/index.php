<?php

/*
 * *************************************************
 * Created on :2015-12-25 12:10:12
 * Encoding   :UTF-8
 * Description:
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */
require_once dirname(__FILE__) . '/../../Daemon/Daemon.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';
require_once dirname(__FILE__) . '/../../vendor/predis/predis/src/Autoloader.php';
require_once dirname(__FILE__) . '/../Class/Http.class.php';

Predis\Autoloader::register();

class AT_Srv_Daemon_TestUi_index extends AT_Srv_Daemon
{
    /**
     *重写构造函数，设置特定的最大子进程数和处理数，如果不需要设置，此方法可以直接继承，不需要重写
     * @param type $daemonName 
     */
    function __construct($daemonName, $MaxProcess, $MaxRequestPerChild)
    {
        parent::__construct($daemonName);
        $this->setMaxProcess($MaxProcess);
        $this->setMaxRequestPerChild($MaxRequestPerChild);
    }
    /**
     * 完善daemon处理函数，此函数必备
     *
     *
     */
    function daemonFunc()
    {
        require dirname(__FILE__) . '/config/config.php';
        $redis = new Predis\Client ($_config['redis_server']);
        $http = new Http();

        while ( $this->subProcessCheck() ) {
            //处理队列
            $case_data = $redis->lpop($_config['queue_name']);
            if(empty($case_data)){
                break;
            }else{
                $arr =json_decode($case_data,true);

                $url = $arr['host'].$arr['url'];


                $query =$arr['param'];

                $method = strtoupper($arr['method']);

                //拼装表单提交数据
                $formdata =array();
                $temp_arry = explode('&',$query);
                foreach($temp_arry as $item){
                    list($k,$v) =explode('=',$item);
                    $formdata[$k]=$v;
                }
                //判断是否需要token
                if(isset($arr['token'])){
                    $formdata['token']=$arr['token'];
                }

                if($method=='GET'){
                    $http->get($url,$formdata);
                }else{
                    $http->post($url,$formdata);
                }
                $res =  $http->getContent();
                //此处增加返回结果的判断
                $result=$arr['result'];
                if($result ==$res){
                    $res_test=1;
                }else{
                    $res_test=0;
                }
//                $req['url'] = $url;
//                $req['data'] = $formdata;

                //$result =array();

                file_put_contents(realpath(dirname(__FILE__)) . '/../../output/demo.log',  $res_test.'|'.$result.'|'.$res.'|'.$url.'|'.json_encode($formdata)  . "\n", FILE_APPEND);
            }

            //增加处理数,不增加处理数，就需要子进程本身有退出机制。
            //$this->requestCount++;
            //释放时间片
            usleep(5000);
        }
    }

}

/* End of file demoDaemon */
