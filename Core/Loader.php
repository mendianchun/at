<?php

/*
 * *************************************************
 * Created on :2015-12-25 12:01:12
 * Encoding   :UTF-8
 * Description:
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */

class AT_Srv_Loader
{
    /**
     * 加载类，参考自Zend Framework
     *
     * @param type $class
     * @param type $dirs
     * @return type 
     */
    public static function loadClass($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }
        //生成文件名
        $shortClassName = substr($class,7);
        if (FALSE === strpos($shortClassName, '_')) {
            $shortClassName = $shortClassName . '/' . $shortClassName;
        }        
        $file = str_replace('_', DIRECTORY_SEPARATOR, $shortClassName) . '.php';
        //模块的默认类，类名里面不会带目录名，自动加上
        //include文件
        self::loadFile($file, true);
        //加载类
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            require_once 'Core/Exception.php';
            throw new AT_Srv_Exception("File \"$file\" does not exist or class \"$class\" was not found in the file");
        }
    }
    /**
     *
     * @param type $fileName
     * @param type $once
     * @return type 
     */
    public static function loadFile($fileName, $once = false)
    {
        self::_securityCheck($fileName);

        if ($once) {
            include_once $fileName;
        } else {
            include $fileName;
        }

        return true;
    }
   
    /**
     *
     * @param type $fileName 
     */
    protected static function _securityCheck($fileName)
    {
        /**
         * Security check
         */
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $fileName)) {
            require_once 'Core/Exception.php';
            throw new AT_Srv_Exception('Security check: Illegal character in filename');
        }
    }
}

/* End of file Loader */