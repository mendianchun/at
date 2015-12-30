<?php

require('Class/HttpRequest.class.php');

$config = array(
            'ip' => 'demo.fdipzone.com', // 如空则用host代替
            'host' => 'demo.fdipzone.com',
            'port' => 80,
            'errno' => '',
            'errstr' => '',
            'timeout' => 30,
            'url' => '/getapi.php',
            //'url' => '/postapi.php',
            //'url' => '/multipart.php'
);

$formdata = array(
    'name' => 'fdipzone',
    'gender' => 'man'
);

$filedata = array(
    array(
        'name' => 'photo',
        'filename' => 'photo.jpg',
        'path' => 'photo.jpg'
    )
);

$obj = new HttpRequest();
$obj->setConfig($config);
$obj->setFormData($formdata);
$obj->setFileData($filedata);
$result = $obj->send('get');
//$result = $obj->send('post');
//$result = $obj->send('multipart');

echo '<pre>';
print_r($result);
echo '</pre>';

?>