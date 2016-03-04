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

require(dirname(__FILE__) . '/excel/Classes/PHPExcel/IOFactory.php');

class testcase
{
    //接口对应的测试用例文件
    private $casefile = '';

    public function __construct($UIname)
    {
        $this->casefile = dirname(__FILE__) . '/case/' . $UIname;
    }

    public function getdata()
    {
        $type = strtolower(pathinfo($this->casefile, PATHINFO_EXTENSION));

        if (!file_exists($this->casefile)) {
            return false;
        }

        //根据不同类型分别操作
        if ($type == 'xlsx' || $type == 'xls') {
            $objPHPExcel = PHPExcel_IOFactory::load($this->casefile);
        } else if ($type == 'csv') {
            $objReader = PHPExcel_IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding('GBK')//不设置将导致中文列内容返回boolean(false)或乱码
                ->setEnclosure('"')
                ->setLineEnding("\r\n")
                ->setSheetIndex(0);
            $objPHPExcel = $objReader->load($this->casefile);
        } else {
            return false;
        }

        //选择标签页
        $sheet = $objPHPExcel->getSheet(0);

        //获取行数与列数,注意列数需要转换
        $highestRowNum = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn);

        //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
        $filed = array();
        for ($i = 0; $i < $highestColumnNum; $i++) {
            $cellName = PHPExcel_Cell::stringFromColumnIndex($i) . '1';
            $cellVal = $sheet->getCell($cellName)->getValue();//取得列内容
            $filed [] = $cellVal;
        }

        //开始取出数据并存入数组
        $data = array();
        for ($i = 2; $i <= $highestRowNum; $i++) {//ignore row 1
            $row = array();
            for ($j = 0; $j < $highestColumnNum; $j++) {
                $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                $cellVal = $sheet->getCell($cellName)->getValue();
                $row[$filed[$j]] = $cellVal;
            }
            $data [] = $row;
        }
        return $data;
    }
}

?>