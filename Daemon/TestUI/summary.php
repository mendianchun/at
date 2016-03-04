<?php
/*
 * *************************************************
 * Created on :2016-01-07 02:37:12
 * Encoding   :UTF-8
 * Description:汇总统计结果，并发布结果。
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 */
error_reporting(0);

$dir = realpath(dirname(__FILE__)) . '/../../output';
require_once dirname(__FILE__) . '/../../class/excel/Classes/PHPExcel.php';
require_once dirname(__FILE__) . '/../../vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
require dirname(__FILE__) . '/../../config/testUI/config.php';

$file = $dir . "/" . $argv[1];
//var_dump($file);
if (!is_file($file)) {
    exit(0);
}

//读取处理日志
$log = file_get_contents($file);
$log_case = explode("\n", trim($log));

$fail_result = array();
$ui_result = array();
$idx = 0;
foreach ($log_case as $case_result) {
    list($ret, $ui_ret, $expect_ret, $url, $param) = explode("|", trim($case_result));

//    echo $ret.",".$ui_ret.",".$expect_ret.",".$url.",".$param."\n";

    //第一次获取接口目前状态为测试成功，才会进行赋值。
    if (!isset($ui_result[$url]) || $ui_result[$url] != '0') {
        $ui_result[$url] = $ret;
    }

    if ($ret == 0) {
        $fail_result[$idx]['ui_ret'] = $ui_ret;
        $fail_result[$idx]['expect_ret'] = $expect_ret;
        $fail_result[$idx]['url'] = $url;
        $fail_result[$idx]['param'] = $param;
        $idx++;
    }
}

$total_ui = 0;
$succ_ui = 0;
$fail_ui = 0;
foreach ($ui_result as $result) {
    $total_ui++;
    if ($result == 1) {
        $succ_ui++;
    } else {
        $fail_ui++;
    }
}
$summary = "总共测试接口:" . $total_ui . "个，成功：" . $succ_ui . "个，失败：" . $fail_ui . "个";
//var_dump($summary, $fail_result);

if (is_array($fail_result)) {
    $objPHPExcel = new PHPExcel();

    $objPHPExcel->getProperties()->setCreator("damen")
        ->setLastModifiedBy("damen")
        ->setTitle("at summary result")
        ->setSubject("at summary result");
// Add some data
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A1', '接口地址')
        ->setCellValue('B1', '参数')
        ->setCellValue('C1', '期望结果')
        ->setCellValue('D1', '实际结果');

    $column = 2;
    foreach ($fail_result as $result) {
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A' . $column, $result['url'])
            ->setCellValue('B' . $column, $result['param'])
            ->setCellValue('C' . $column, $result['expect_ret'])
            ->setCellValue('D' . $column, $result['ui_ret']);
        $column++;
    }
//$objPHPExcel->getActiveSheet()->setCellValue('A8', "Hello\nWorld");
//$objPHPExcel->getActiveSheet()->getRowDimension(8)->setRowHeight(-1);
//$objPHPExcel->getActiveSheet()->getStyle('A8')->getAlignment()->setWrapText(true);
//$value = "-ValueA\n-Value B\n-Value C";
//$objPHPExcel->getActiveSheet()->setCellValue('A10', $value);
//$objPHPExcel->getActiveSheet()->getRowDimension(10)->setRowHeight(-1);
//$objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setWrapText(true);
//$objPHPExcel->getActiveSheet()->getStyle('A10')->setQuotePrefix(true);
// Rename worksheet
    $objPHPExcel->getActiveSheet()->setTitle('result');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
// Save Excel 2007 file
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($dir . "/testUI.xlsx");

    $mail = new PHPMailer(); //实例化
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host = "smtp.exmail.qq.com"; //SMTP服务器 以163邮箱为例子
    $mail->Port = 25;  //邮件发送端口
    $mail->SMTPAuth   = true;  //启用SMTP认证

    $mail->CharSet  = "UTF-8"; //字符集
    $mail->Encoding = "base64"; //编码方式

    $mail->Username = "mendianchun@acttao.com";  //你的邮箱
    $mail->Password = "mdc,195010";  //你的密码
    $mail->Subject = "testUI测试报告".date("Y-m-d"); //邮件标题

    $mail->From = "mendianchun@acttao.com";  //发件人地址（也就是你的邮箱）
    $mail->FromName = "大门";  //发件人姓名
//var_dump($_config['mail_to']);
//    exit;
    $address_list = $_config['mail_to'];//收件人email
    $address_array = explode(";",$address_list);
    foreach($address_array as $address){
        $mail->AddAddress($address, "亲");//添加收件人（地址，昵称）
    }

    $mail->AddAttachment($dir . "/testUI.xlsx",'测试报告.xlsx'); // 添加附件,并指定名称
    $mail->IsHTML(true); //支持html格式内容
    $mail->Body = '测试结果:'.$summary;

//发送
    if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {
        echo "Message sent!";
    }
}
