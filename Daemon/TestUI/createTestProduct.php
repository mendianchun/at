<?php
/*
 * *************************************************
 * Created on :2015-12-26 10:18:12
 * Encoding   :UTF-8
 * Description:导入测试数据
 *
 * @Author 大门 <mendianchun@acttao.com>
 * ************************************************
 *
 * 测试环境创建商品

目标

商品模版：2－5万个
商品模版SKU：10万个
商品SKU：目标50万个

品牌：11个
三级分类：176个


按照分类和品牌组合创建商品，共1936个分类和品牌的组合。

按照商品模版2-5万个的目标，每个组合下需要创建10-25个商品模版，商品模版命名规则：品牌名称＋分类名称＋递增的数字

每个商品需要有2-5个sku，规格统一用平台通过的容量	10ml,25ml,50ml,75ml,100ml

目前经销商5家，按照商品SKU 50万的目标，每个商家需要代理所有的商品。

 */
error_reporting(0);
require dirname(__FILE__) . '/../Class/db.class.php';

$_config = array(
    'mysql_server' => array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => 'root',
        'dbname' => 'mm',
        'dbcharset' => 'UTF8'
    )
);
//初始化数据
$db = new db;
$db->connect($_config['mysql_server']['host'] . ":" . $_config['mysql_server']['port'], $_config['mysql_server']['user'], $_config['mysql_server']['password'], $_config['mysql_server']['dbname'], $_config['mysql_server']['dbcharset']);

//获取三级分类
$result_category = array();
$sql_category = 'select * from products_category where level = 2';
$db->fetch_all($sql_category, $result_category);
//var_dump($result_category);
//exit;
//获取品牌
$result_brand = array();
$sql_brand = 'select * from products_brand';
$db->fetch_all($sql_brand, $result_brand);

//获取商品图片
$result_image = array();
$sql_image = 'select * from products_image';
$db->fetch_all($sql_image, $result_image);
foreach ($result_image as $image) {
    $image_array[] = $image['id'];
}

//获取所有经销商id
$result_store = array();
$sql_store = 'select * from stores_store';
$db->fetch_all($sql_store, $result_store);
foreach ($result_store as $store) {
    $store_array[] = $store['id'];
}

if ($argv[1] == 'template') {
    //$product_template = array();
    $spec = array('id' => 60, 'value' => '10ml,25ml,50ml,75ml,100ml', 'name' => '容量');
    $json_spec = json_encode($spec);
//    $count++;
    foreach ($result_brand as $brand) {
        $brand_name = trim($brand['name']);
        $brand_id = intval($brand['id']);
        foreach ($result_category as $category) {
            $category_name = trim($category['name']);
            $category_id = intval($category['id']);

            //获取分类的上级id
            $result_category_keywords = array();
            $sql = 'select keywords from products_category where id = ' . $category_id;
            $db->fetch_all($sql, $result_category_keywords);
            $category_keywords = $result_category_keywords[0]['keywords'];
            list($category_id_1, $category_id_2, $category_id_3) = explode(":", $category_keywords);

            //取一级分类
            $result_category_1 = array();
            $sql = "select * from products_category where id = " . $category_id_1;
            $db->fetch_all($sql, $result_category_1);

            //取二级分类
            $result_category_2 = array();
            $sql = "select * from products_category where id = " . $category_id_2;
            $db->fetch_all($sql, $result_category_2);

            //每个组合下需要创建25个商品模版
            for ($i = 1; $i <= 25; $i++) {
//                echo $count++;
//                echo "\n";
                $product_template_name = $brand_name . $result_category_1[0]['name'] . $result_category_2[0]['name'] . $category_name . '商品' . $i;
                $product_template_per['created'] = date("Y-m-d H:i:s");
                $product_template_per['name'] = $product_template_name;
                $product_template_per['category_id_1'] = $category_id_1;
                $product_template_per['category_id_2'] = $category_id_2;
                $product_template_per['category_id_3'] = $category_id_3;
                $product_template_per['category_id'] = $category_id_3;
                $product_template_per['brand_id'] = $brand_id;
                $product_template_per['body'] = $product_template_name;
                $product_template_per['mobile_body'] = $product_template_name;
                $product_template_per['state'] = 1;
                $product_template_per['verify'] = 1;
                $product_template_per['goods_spec'] = $json_spec;

//            $product_template[] = $product_template_per;

                //生成商品模版
                $sql = "insert into products_goods (created,modified,name,category_id_1,category_id_2,category_id_3,category_id,brand_id,state,verify,body,mobile_body,goods_spec,is_active)
                    values('" . date("Y-m-d H:i:s") . "',
                            '" . date("Y-m-d H:i:s") . "',
                            '" . $product_template_name . "',
                            " . $category_id_1 . ",
                            " . $category_id_2 . ",
                            " . $category_id_3 . ",
                            " . $category_id_3 . ",
                            " . $brand_id . ",
                            1,
                            1,
                            '" . $product_template_name . "',
                            '" . $product_template_name . "',
                            '" . $json_spec . "',
                            1
                    )";
//            die($sql);

                $db->query($sql);
                $goods_id = $db->insert_id();
                $image_id = array_rand($image_array);

                //与商品图片关联,生成sku
                if (!$db->errno()) {

                    //与商品图片关联
                    $sql = "insert into products_goodsimageship (goods_id,image_id,is_default,is_active) values(" . $goods_id . "," . $image_id . ",1,1)";
                    $db->query($sql);

                    //生成sku
                    $all_spec_value = explode(",", $spec['value']);
                    foreach ($all_spec_value as $sku_spec_value) {
                        $sku_spec = array('name' => '容量', 'value' => $sku_spec_value, 'id' => 60);
                        $sql = "insert into products_sku (created,modified,goods_id,name,price,goods_spec,state,is_active)
                            values('" . date("Y-m-d H:i:s") . "',
                                    '" . date("Y-m-d H:i:s") . "',
                                    " . $goods_id . ",
                                    '" . $product_template_name . $sku_spec['name'] . $sku_spec_value . "',
                                    " . rand(50, 100) . ",
                                    '" . json_encode($sku_spec) . "',
                                    1,
                                    1
                            )";
                        $db->query($sql);
                    }
                }
            }
//            unset($result_category_keywords);
//            unset($result_category_1);
//            unset($result_category_2);
        }
    }
} elseif ($argv[1] == 'store') {
//    var_dump($db);
    //经销商代理商品
    $result_product_template = array();
    $sql = 'select id,goods_spec from products_goods where created > "2016-01-05"';
    $db->fetch_all($sql, $result_product_template);
//    var_dump($db);
//    var_dump(count($result_product_template));
//    exit;
    foreach ($result_product_template as $product_template) {
        $goods_id = $product_template['id'];
        $goods_spec = $product_template['goods_spec'];
//var_dump($goods_id,$goods_spec);
        //获取模版的sku
        $result_product_template_sku = array();
        $sql = "select id,price from products_sku where goods_id = " . $goods_id;
        $db->fetch_all($sql, $result_product_template_sku);
        foreach ($store_array as $store) {
            $store_id = $store['id'];

            $sql = "insert into products_storegoods (created,store_id,goods_id,state,is_active,goods_spec)
                    values('" . date("Y-m-d H:i:s") . "',
                        " . $store_id . ",
                        " . $goods_id . ",
                        1,
                        1,
                        '" . $goods_spec . "'
                    )";
            $db->query($sql);
            $store_goods_id = $db->insert_id();

            foreach ($result_product_template_sku as $product_template_sku) {
                $sku_id = $product_template_sku['id'];
                $price = $product_template_sku['price'];
                $sql = "insert into products_storesku (created,sku_id,store_goods_id,store_id,price,storage,state,is_active)
                    values('" . date("Y-m-d H:i:s") . "',
                        " . $sku_id . ",
                        " . $store_goods_id . ",
                        " . $store_id . ",
                        " . $price . ",
                        100,
                        1,
                        1
                    )";
                $db->query($sql);
            }
            reset($result_product_template_sku);
        }
    }
}

