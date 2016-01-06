<?php
/**
 * Copyright (c) 2012, daly
 * $Id: class.Http.php
 * Http操作
 * Author::2012-09-10 daly
//[2] 发送GET请求
$http->get('http://www.sina.com.cn/');

//[3] 取得返回的头数据
print_r($http->getHeaders());

//[4] 取得返回的数据
echo $http->getContent();
 */

define('HTTP_METHOD_GET', 'GET');
define('HTTP_METHOD_POST', 'POST');

class Http {
    var $host;
    var $port;
    var $path;
    var $method;
    var $postdata = '';
    var $cookies = array();
    var $referer;
    var $accept = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
    var $accept_encoding = 'gzip';
    var $accept_language = 'zh-cn,zh;q=0.5';
    var $user_agent = 'Http 1.0';

    var $timeout = 3;
    var $use_gzip = false;
    var $persist_cookies = true;
    var $persist_referers = true;
    var $debug = false;
    var $handle_redirects = true;
    var $max_redirects = 5;
    var $headers_only = false;

    var $username;
    var $password;

    var $status;
    var $headers = array();
    var $content = '';
    var $errormsg;

    var $redirect_count = 0;
    var $cookie_host = '';

    function __construct() {

    }


    /**
     * 设置超时时间
     * @param $t 秒
     */
    function setTimeout($t) {
        $this->timeout = $t;
    }

    /**
     * 设置地址
     * @param $host 地址
     * @param $port 端口
     */
    function setAddr($host, $port = 80) {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * 获取GET信息
     *
     * @param string $uri URI地址，所以不需要添加具体域名和http
     * @param mix $data 需要传递的数据 可以是 string 或者 array
     * @return bool 0:不成功, 1:成功
     */
    function doGet($uri, $data = false) {
        $this->path = $uri;
        $this->method = HTTP_METHOD_GET;
        if ($data) {
            $this->path .= '?'.$this->buildQueryString($data);
        }
        return $this->doRequest();
    }

    /**
     * 获取POST信息
     *
     * @param string $uri URI地址，所以不需要添加具体域名和http
     * @param mix $data 需要传递的数据 可以是 string 或者 array
     * @return bool 0:不成功, 1:成功
     */
    function doPost($uri, $data) {
        $this->path = $uri;
        $this->method = HTTP_METHOD_POST;
        $this->postdata = $this->buildQueryString($data);
        return $this->doRequest();
    }

    function buildQueryString($data) {
        $querystring = '';
        if (is_array($data)) {
            // Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $querystring .= urlencode($key).'='.urlencode($val2).'&';
                    }
                } else {
                    $querystring .= urlencode($key).'='.urlencode($val).'&';
                }
            }
            $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {
            $querystring = $data;
        }
        return $querystring;
    }

    /**
     * 发起请求
     * @return 返回是否请求成功
     */
    function doRequest() {

        if (!$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
            switch($errno) {
                case -3:
                    $this->errormsg = 'Socket creation failed (-3)';
                case -4:
                    $this->errormsg = 'DNS lookup failure (-4)';
                case -5:
                    $this->errormsg = 'Connection refused or timed out (-5)';
                default:
                    $this->errormsg = 'Connection failed ('.$errno.')';
                    $this->errormsg .= ' '.$errstr;
                    $this->debug($this->errormsg);
            }
            //echo $errno;
            //writelog('ssolog', $errno.'|'.$errstr);
            //CakeLog::write('http_error', $errno.'|'.$errstr);
            return false;
        }

        //socket_set_timeout($fp, $this->timeout);
        socket_set_timeout($fp, 10);
        $request = $this->buildRequest();
//		echo $request;exit;
        //发出请求
        fwrite($fp, $request);

        $this->headers = array();
        $this->content = '';
        $this->errormsg = '';
        $inHeaders = true;
        $atStart = true;

        $status = stream_get_meta_data($fp);

        $data = '';


        if(!$status['timed_out']) {
            $data = @fgets($fp);

            if (!preg_match('/HTTP\/(\d\.\d)\s+(\d+)\s+(.*)/', $data, $m)) {
                return false;
            }

            $http_version = $m[1]; $this->status = $m[2]; $status_string = $m[3];


            while (!feof($fp)) {
                if($data = fgets($fp)) {

                    if($data == "\r\n" || $data == "\n") {
                        break;
                    } else {
                        if (!preg_match('/([^:]+):\\s*(.*)/', $data, $m)) {
                            continue;
                        }
                        $key = strtolower(trim($m[1]));
                        $val = trim($m[2]);
                        if (isset($this->headers[$key])) {
                            if (is_array($this->headers[$key])) {
                                $this->headers[$key][] = $val;
                            } else {
                                $this->headers[$key] = array($this->headers[$key], $val);
                            }
                        } else {
                            $this->headers[$key] = $val;
                        }
                    }
                }
            }

            if (!$this->headers_only) {
                $data = '';
                while(!feof($fp)) {
                    $data = fread($fp, 8192);
                    $this->content .= $data;
                }
            }
        }
        @fclose($fp);

        // If data is compressed, uncompress it
        if (isset($this->headers['content-encoding']) && $this->headers['content-encoding'] == 'gzip') {
            $this->debug('Content is gzip encoded, unzipping it');
            $this->content = substr($this->content, 10); // See http://www.php.net/manual/en/function.gzencode.php
            $this->content = gzinflate($this->content);
        }
        // If $persist_cookies, deal with any cookies
        if ($this->persist_cookies && isset($this->headers['set-cookie'])) {
            $cookies = $this->headers['set-cookie'];
            //print_r($cookies); echo "<hr>";
            if (!is_array($cookies)) {
                $cookies = array($cookies);
            }

            foreach ($cookies as $cookie) {

                $p = explode(';', $cookie);

                list($cname, $cval) = explode('=', $p[0]);

                $this->cookies[trim($cname)]['value'] = $cval;

                foreach ($p as $cval) {
                    list($ckey, $crow) = explode('=', $cval);
                    $ckey = ltrim($ckey);
                    if($ckey == 'path') {
                        $this->cookies[$cname][$ckey] = $crow;
                    } elseif($ckey == 'expires') {
                        $this->cookies[$cname][$ckey] = strtotime($crow);
                    }
                }
            }
            // Record domain of cookies for security reasons
            $this->cookie_host = $this->host;
        }
        // If $persist_referers, set the referer ready for the next request
        if ($this->persist_referers) {
            $this->debug('Persisting referer: '.$this->getRequestURL());
            $this->referer = $this->getRequestURL();
        }
        // Finally, if handle_redirects and a redirect is sent, do that
        if ($this->handle_redirects) {
            if (++$this->redirect_count >= $this->max_redirects) {
                $this->errormsg = 'Number of redirects exceeded maximum ('.$this->max_redirects.')';
                $this->debug($this->errormsg);
                $this->redirect_count = 0;
                return false;
            }
            $location = isset($this->headers['location']) ? $this->headers['location'] : '';
            $uri = isset($this->headers['uri']) ? $this->headers['uri'] : '';
            if($location || $uri) {
                $url = parse_url($location.$uri);
                if(!empty($url['host'])) {
                    $this->setAddr($url['host']);
                }
                return $this->doGet($url['path'], $url['query']);
            }
        }
        return true;
    }

    function buildRequest() {
        $headers = array();
        $headers[] = "$this->method $this->path HTTP/1.0";
        $headers[] = "Host: {$this->host}";
        $headers[] = "User-Agent: {$this->user_agent}";
        $headers[] = "Accept: {$this->accept}";
        if ($this->use_gzip) {
            $headers[] = "Accept-encoding: {$this->accept_encoding}";
        }
        $headers[] = "Accept-language: {$this->accept_language}";
        if ($this->referer) {
            $headers[] = "Referer: {$this->referer}";
        }
        // Cookies
        if ($this->cookies) {
            $cookie = 'Cookie: ';
            foreach ($this->cookies as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            $headers[] = $cookie;
        }
        // Basic authentication
        if ($this->username && $this->password) {
            $headers[] = 'Authorization: BASIC '.base64_encode($this->username.':'.$this->password);
        }
        // If this is a POST, set the content type and length
        if ($this->postdata) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $headers[] = 'Content-Length: '.strlen($this->postdata);
        }
        $request = implode("\r\n", $headers)."\r\n\r\n".$this->postdata;
        return $request;
    }

    function getStatus() {
        return $this->status;
    }

    function getContent() {
        return $this->content;
    }

    function getHeaders() {
        return $this->headers;
    }

    function getHeader($header) {
        $header = strtolower($header);
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        } else {
            return false;
        }
    }

    function getError() {
        return $this->errormsg;
    }

    function getCookies() {
        return $this->cookies;
    }

    function getRequestURL() {
        $url = 'http://'.$this->host;
        if ($this->port != 80) {
            $url .= ':'.$this->port;
        }
        $url .= $this->path;
        return $url;
    }

    // Setter methods
    function setUserAgent($string) {
        $this->user_agent = $string;
    }

    function setAuthorization($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    function setCookies($array) {
        $this->cookies = $array;
    }

    // Option setting methods
    function useGzip($boolean) {
        $this->use_gzip = $boolean;
    }

    function setPersistCookies($boolean) {
        $this->persist_cookies = $boolean;
    }

    function setPersistReferers($boolean) {
        $this->persist_referers = $boolean;
    }

    function setHandleRedirects($boolean) {
        $this->handle_redirects = $boolean;
    }

    function setMaxRedirects($num) {
        $this->max_redirects = $num;
    }

    function setHeadersOnly($boolean) {
        $this->headers_only = $boolean;
    }

    function setDebug($boolean) {
        $this->debug = $boolean;
    }

    /**
     * 直接通过地址GET数据
     *
     * @param string $url URL地址 需要带上http://开头
     * @return bool 0:不成功, 1:成功
     */
    function get($url,$data=false) {
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        if (isset($bits['query'])) {
            $path .= '?'.$bits['query'];
        }
        $this->setAddr($host, $port);
        return $this->doGet($path, $data);
    }

    /**
     * 直接通过地址POST数据
     *
     * @param string $url URL地址 需要带上http://开头
     * @param mix $data 需要发送的数据 字符串或者数组
     * @return bool 0:不成功, 1:成功
     */
    function post($url, $data) {
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        $this->setAddr($host, $port);
        return $this->doPost($path, $data);
    }


    function debug($msg, $object = false) {
        if ($this->debug) {
            print '<div style="border: 1px solid red; padding: 0.5em; margin: 0.5em;"><strong>HttpClient Debug:</strong> '.$msg;
            if ($object) {
                ob_start();
                print_r($object);
                $content = htmlentities(ob_get_contents());
                ob_end_clean();
                print '<pre>'.$content.'</pre>';
            }
            print '</div>';
        }
    }
}

?>