<?php
/************************************************************
** @Description: 基础函数库
** @Author: haodaquan
** @Date:   2016-06-03 12:21:01
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-25 13:36:38
*************************************************************/
/**
 * [authcode 加密函数]
 * @Date   2016-06-03
 * @param  [type]     $string    [description]
 * @param  string     $operation [description]
 * @param  string     $key       [description]
 * @param  integer    $expiry    [description]
 * @return [type]                [description]
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 2592000)
{
    $oristr = $string;
    $orikey = $key;
    $string = $oristr;
    $key = $orikey;
    $ckey_length = 4;
    $key = md5 ( $key ? $key : '6g1Y780G2udU5seSeecSdb1' );
    $keya = md5 ( substr ( $key, 0, 16 ) );
    $keyb = md5 ( substr ( $key, 16, 16 ) );
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';
    $cryptkey = $keya . md5 ( $keya . $keyc );
    $key_length = strlen ( $cryptkey );
    if ($operation == 'DECODE') {
        $string = str_replace ( '.', '+', $string );
        $string = str_replace ( '-', '/', $string );
        $string = base64_decode ( substr ( $string, $ckey_length ) );
    } else {
        $string = sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
    }
    $string_length = strlen ( $string );
    $result = '';
    $box = range ( 0, 255 );
    $rndkey = array ();
    for($i = 0; $i <= 255; $i ++) {
        $rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
    }
    for($j = $i = 0; $i < 256; $i ++) {
        $j = ($j + $box [$i] + $rndkey [$i]) % 256;
        $tmp = $box [$i];
        $box [$i] = $box [$j];
        $box [$j] = $tmp;
    }
    for($a = $j = $i = 0; $i < $string_length; $i ++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box [$a]) % 256;
        $tmp = $box [$a];
        $box [$a] = $box [$j];
        $box [$j] = $tmp;
        $result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
    }
    if ($operation == 'DECODE') {
        if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
            return substr ( $result, 26 );
        } else {
            return '';
        }
    } else {
        $result = str_replace ( '=', '', base64_encode ( $result ) );
        $result = str_replace ( '+', '.', $result );
        $result = str_replace ( '/', '-', $result );
        return $keyc . $result;
    }
}

/**
 * 浏览器友好的变量输出,调试函数
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}


/**
 * [getButton 生成操作按钮]
 * @Author haodaquan
 * @Date   2016-04-07
 * @param  int        $id     [操作id]
 * @param  string     $btnArr [array('edit'=>'编辑','delete'=>'删除')]
 * @return [type]             [button字符串]
 */
function getButton($id=0,$btnArr='')
{
    
    if (empty($btnArr) || $id===0) return '';
    $btn = '';
    $relation = [
            'detail'        => 'info', //查看
            'edit'          => 'primary',//编辑
            'delete'        => 'danger',//删除
            'changeStatus'  => 'warning',//改变状态
            'disable'       => 'default',//禁止或者默认
            'default'       => 'success',//普通操作
            'tabEdit'       => 'primary',//编辑
            

        ];

    foreach ($btnArr as $key => $value) 
    {
        if(strpos($key,"noAuth")===false){
           $btn .= '<button type="button" onClick="return '.$key.'Action('.$id.')" class="btn btn-xs btn-'.$relation[$key].'">'.$value.'</button>&nbsp;'; 
       }else{
            $btn .= '<button type="button" class="btn btn-xs btn-default" disabled="disabled">'.$value.'</button>&nbsp;'; 
       }
        
    }
    return $btn;
}

/**
 * [curl_request 接口请求函数]
 * @param  [type] $url    [地址]
 * @param  [type] $post   [数据]
 * @param  [type] $header [header头数据]
 * @return [type]         [description]
 */
function curl_request($url, $post = null, $header = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($post) {
        if (is_array($post)) {
            $post = http_build_query($post);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_POST, 1);
    } else {
        $postData = "";
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($post)));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT,180);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * [make_dir 创建文件夹]
 * @Date   2016-06-30
 * @param  string     $file_path [文件地址]
 * @return [type]                [description]
 */
function make_dir($file_path)
{
    //$date_file_path = $file_path;
    if (!is_dir($file_path)) return  mkdir($file_path,0777,true);
    return true;
}

/**
 * [change_array_map 字段映射的关联数组转化]
 * @param  [type] $map [映射关系，键名与arr对应]
 * @param  [type] $arr [数组]
 * @return [type]      [description]
 */
function change_array_map($map,$arr)
{
    $new_arr = [];
    foreach ($arr as $key => $value) {
        if(isset($arr[$key])) $new_arr[$map[$key]] = $arr[$key];
        continue;
    }

    return $new_arr;
}

/**
 * [tree 获取文件树]
 * @param  [type] $directory [文件夹名称]
 * @return [type]            [description]
 */
function tree($directory) 
{ 
    $mydir = dir($directory);
    if(!$mydir) return false;

    $data = [];
    while($file = $mydir->read())
    { 
        if (($file==".") || ($file=="..") || ($file==".DS_Store")) continue;
        $data[$file] = is_dir("$directory/$file") ? tree("$directory/$file") : $file;
    } 
    $mydir->close(); 

    return $data;
} 


/**导出excel
 * @param $head 表头中文
 * @param $fields 字段列表
 * @param $data 数据集合
 * @param $name 文件名
 */
function export_excel($head, $fields,$data, $name)
{
    require_once "application/libraries/php_excel/lib/Classes/PHPExcel.php";
    $key_array = array();
    for ($i = 0; $i < 26; $i++) {
        $key_array[] = chr($i + 65);
    }
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $objActSheet = $objPHPExcel->getActiveSheet();
    $objActSheet->setTitle("Sheet");
    $objDrawing = new PHPExcel_Worksheet_Drawing();
    /*$temp_img = "application/helpers/temp_img";
    mkdir($temp_img);
    $objActSheet->getDefaultRowDimension()->setRowHeight(80);*/
    foreach($head as $key=>$value) {
        $column = num_to_excel_column($key + 1, $key_array);
        $objActSheet->setCellValueExplicit($column . 1, $value, PHPExcel_Cell_DataType::TYPE_STRING);
    }
    foreach ($data as $k => $obj) {
        $num = $k + 2;
        $j = 1;
        foreach($fields as $field){
            $column = num_to_excel_column($j,$key_array);
            /*if($j==1){
                $objActSheet->getColumnDimension($column)->setWidth(40);
                $objDrawing = new PHPExcel_Worksheet_Drawing();
                url_to_local_img($obj[$field],$temp_img."/".$k.".jpg");
                $objDrawing->setPath($temp_img."/".$k.".jpg");
                $objDrawing->setHeight(80);
                $objDrawing->setWidth(80);
                $objDrawing->setCoordinates($column.$num);
                $objDrawing->setOffsetX(50);
                $objDrawing->setWorksheet($objActSheet);
            }else{
                $objActSheet->setCellValueExplicit($column.$num, $obj[$field], PHPExcel_Cell_DataType::TYPE_STRING);
            }*/
            $objActSheet->setCellValueExplicit($column.$num, $obj[$field], PHPExcel_Cell_DataType::TYPE_STRING);
            $j++;
        }
    }
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($name.".xlsx");
     /*header('Content-Type: application/vnd.ms-excel');
     header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
     header('Cache-Control: max-age=0');
     $objWriter->save('php://output');*/
     /*delete_dir($temp_img);*/
}

//数字转换为A、B...AZ
function num_to_excel_column($n, $key_array) {
    $str = "";
    while($n>0){
        $yu = $n%26;
        $n = intval($n/26);
        if($yu==0){
            $str = $str.$key_array[25];
            $n--;
        }else{
            $str = $str.$key_array[$yu-1];
        }
    }
    return strrev($str);
}

/**
 * [set_headers 设置文件头]
 * @Date  2016-07-11
 * @param [type]     $file_path  [description]
 * @param [type]     $excel_name [description]
 */
function set_headers($file_path,$excel_name) 
{
   //文件的类型 
    header("Content-Type: application/force-download");
    //下载显示的名字 
    header('Content-Disposition: attachment; filename="'.$excel_name.'"'); 
    readfile($file_path); 
    exit(); 
}




/**
 * [http 调用接口函数]
 * @Date   2016-07-11
 * @Author GeorgeHao
 * @param  string       $url     [接口地址]
 * @param  array        $params  [数组]
 * @param  string       $method  [GET\POST\DELETE\PUT]
 * @param  array        $header  [HTTP头信息]
 * @param  integer      $timeout [超时时间]
 * @param  boolean      $sign    [是否加密]
 * @return [type]                [接口返回数据]
 */
function http($url, $params, $method = 'GET', $header = array(), $timeout = 10,$sign=false)
{
    $opts = array(
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );

    if($sign)
    {
        $ts = time();
        $check =[
            "app_key=" . APP_KEY,
            "app_secret=" . APP_SECRET,
            "method=" . $method,
            "ts=" . $ts];
        sort($check);      
        $url .= '?sign='.md5(sha1(join("&", $check))).
                '&ts='.$ts.'&app_key='.APP_KEY
                .'&method='.$method;
    }
    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            if($params)
            {
               $opts[CURLOPT_URL] = $url . '?' . http_build_query($params); 
            }else
            {
                $opts[CURLOPT_URL] = $url;
            }
            break;
        case 'POST':
            $params = http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        case 'DELETE':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_HTTPHEADER] = array("X-HTTP-Method-Override: DELETE");
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        case 'PUT':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 0;
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
            break;
        case 'PATCH':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 0;
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
  
    /* 初始化并执行curl请求 */
    $ch     = curl_init();
    curl_setopt_array($ch, $opts);
    $data   = curl_exec($ch);
    $error  = curl_error($ch);
    return $data;
}


/**
 * action：
 * GET 获取信息
 * CREATE 创建信息
 * UPDATE 更新信息
 * DELETE 删除信息
 */
function api_curl_request($url, $action = 'GET', $post = null, $header = null)
{
    static $app_key = 'xy_app';
    static $app_secret = 'e7b0c558d00f0e91c235d02296151a8e';
    $ts = time();
    $check = [
        "app_key=" . $app_key,
        "app_secret=" . $app_secret,
        "action=" . $action,
        "ts=" . $ts
    ];
    sort($check);
    $sign =  md5(sha1(join("&", $check)));
    
    // if(isset(parse_url($url)['query'])){
    //     $url .= '&';
    // }else{
    //     $url .= '?';
    // }
    // $url .= 'app_key='.$app_key.'&action='.$action.'&ts='.$ts.'&sign='.$sign;
    $header[] = "appkey:".$app_key;
    $header[] = "action:".$action;
    $header[] = "ts:".$ts;
    $header[] = "sign:".$sign;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($post) {
        if (is_array($post)) {
            $post = http_build_query($post);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_POST, 1);
    } else {
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($post)));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


/**
 * [format_ajax_data 格式化ajax 传过来的字符串]
 * @Date   2016-06-12
 * @param  [type]     $str [name=val&name1=val2]
 * @return [type]          [description]
 */
function format_ajax_data($str)
{
    $perfs = explode("&", $str);
    $data = [];
    foreach($perfs as $perf) {
        $perf_key_values = explode("=", $perf);
        $_perf_key_values =  isset($perf_key_values[1])?$perf_key_values[1]:'';
        $data[urldecode($perf_key_values[0])] = urldecode($_perf_key_values);
    }
    return $data;
}



/**
 * [str_to_realstr 将字符串转为真正的字符串，查询等使用 如 “a,b,c”=>"a","b","c"]
 * @param  [type] $str [含有分隔符的字符串]
 * @param  [type] $sign [含有分隔符的字符串]
 * @return [type]      [description]
 */
function str_to_realstr($str,$sign=",")
{
    if(!$str) return "";
    $str = rtrim($str,$sign);
    if(strpos($str,$sign)===false) return "'".$str."'";
    
    $str_arr = explode($sign,$str);
    $realstr = '';
    foreach ($str_arr as $key => $value) {
        $realstr .="'".$value."'".$sign."";
    }
    return rtrim($realstr,$sign);
}



/**
 * 生成随机的验证码
 * @param  int $count 验证码位数
 */
function createVerifyCode($count=6)
{
    $res = '';
    $str = '0123456789';
    for ($i=0; $i < $count; $i++) { 
        $str = str_shuffle($str);
        $res .= $str[8];
    }
    return $res;
}

/**
 * [debug_ 调试函数]
 * @return [type] [description]
 */
function debug_()
{
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
}

/**
 * [inject_check sql过滤，防止注入]
 * @param  [type] $sql_str [description]
 * @return [type]          [true or false]
 */
function inject_check($sql_str)  
{  
    return preg_match('/^select|insert|and|or|create|update|delete|alter|count|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i', $sql_str); // 进行过滤  
}


/**
 * [get_images_by_url curl方式下载图片]
 * @Date   2016-05-30
 * @param  string     $url      [图片地址]
 * @param  string     $filename [存储时图片地址+名称]
 * @return string               [图片真实地址]
 */
function get_images_by_url($url='',$filename='')
{
    $hander = curl_init();
    $fp = fopen($filename,'wb');
    curl_setopt($hander,CURLOPT_URL,$url);
    curl_setopt($hander,CURLOPT_FILE,$fp);
    curl_setopt($hander,CURLOPT_HEADER,0);
    curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
    #curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
    curl_setopt($hander,CURLOPT_TIMEOUT,60);
    curl_exec($hander);
    curl_close($hander);
    fclose($fp);
    return $filename;
}

//json中文编码
function json_cn_encode($arr)
{
    return urldecode(json_encode(dfs_json($arr)));
}
function dfs_json($arr)
{
    if(is_array($arr)){
        foreach($arr as $key=>$value){
            $en_key = urlencode($key);
            $arr[$en_key] = dfs_json($value);
            if($en_key!=$key){
                unset($arr[$key]);
            }
        }
    }else{
        if(gettype($arr)=="string"){
            $arr = urlencode($arr);
        }
    }
    return $arr;
}

function object_to_array($obj){
    if(is_array($obj)){
        return $obj;
    }
    $_arr = is_object($obj)? get_object_vars($obj) :$obj;
    foreach ($_arr as $key => $val){
        $val=(is_array($val)) || is_object($val) ? object_to_array($val) :$val;
        $arr[$key] = $val;
    }
    return $arr;
}

function is_ajax() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    } else {
        return false;
    }
}

function get_category_array(){
    /*return [
        '生物质资源'     =>['木柴燃料','畜禽粪便','城市废物','农作物废弃物','能源植物'],
        '生物质炭'       =>['木炭','竹炭','秸秆炭','稻壳炭'],
        '生物质应用转化'  =>['生物质发电','生物液体燃料','生物固体成型燃料','沼气','生物质供热'],
        '生物质能技术'   =>['直接燃烧技术','物化转化技术','生化转化技术','植物油技术','生物质压缩成型技术'],
        '其他'          =>['其他']
    ];*/
    return [
        '生物基高分子材料' =>['聚赖氨酸', '聚苹果酸', '聚乳酸', '二氧化碳塑料', '聚对苯二甲酸1','3-丙二醇脂', '聚羟基脂肪酸酯', '聚丁二酸丁二醇酯'],
        '生物基化学品'    =>['丁二酸', '1,3丙二醇', '丙酮酸', '异山梨醇', '乙二酸', '丙烯酸酸'],
        '生物质燃料'      =>['生物沼气', '木柴燃料', '畜禽粪便', '城市废物', '农作物废弃物', '能源植物', '生物质炭', '生物质柴油', '生物液体燃料', '生物固体成型燃料'],
        '生物质能技术'    =>['直接燃烧技术', '物化转化技术', '生化转化技术', '植物油技术', '生物质压缩成型技'],
        '非粮生物质预处理' =>['生物质酶解', '蒸汽爆破技术']
    ];
}

function get_category_root($category){
    $arr = get_category_array();
    foreach($arr as $category_root => $cate_arr){
        if(in_array($category,$cate_arr)){
            return $category_root;
        }
    }
    return '';
}

function get_research_domain(){
    return [
        '生物基高分子材料',
        '生物基化学品',
        '生物质燃料',
        '生物质能技术',
        '非粮生物质预处理'
    ];
}

function get_expert_type(){
    return [
        '院士专家',
        '万人计划',
        '千人计划',
        '百人计划',
        '杰出青年',
        '领军人才',
        '课题组长',
        '科研骨干',
        '其他'
    ];
}

function get_filter_institute(){
    return [
        '-',
        '_',
        '新能源资讯',
        '北极星电力新闻网',
        '北极星储能网',
        '新能源资讯',
        '中国新能源网',
        '中国专业的工程信息资源平台',
        '北极星火电招聘网',
        '中新网',
        '北极星环保会展网'
    ];
}
/**
 * [splitString 分割字符串]
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function splitString($str)
{
    if (!$str) {
        return [$str];
    }
    $str = str_replace("；","|",str_replace(';','|',str_replace("，","|",str_replace(",", "|", $str))));
    return explode("|", $str);
}

/**
 * [reportno 报告编号]
 * @return [type] [description]
 */
function reportno()
{
    return date("YmdHis",intval(time())).rand(1000,9999);
}

/**
 * [docno 文献编号]
 * @return [type] [description]
 */
function docno()
{
    return date("YmdHis",intval(time())).rand(1000,9999);
}

/**
 * [subjectno 专题编号]
 * @return [type] [description]
 */
function subjectno()
{
    return date("YmdHis",intval(time())).rand(1000,9999);
}

/**
 * [projectno 项目编号]
 * @return [type] [description]
 */
function projectno()
{
    return date("YmdHis",intval(time())).rand(1000,9999);
}

/**
 * [expertno 专家编号]
 * @return [type] [description]
 */
function expertno()
{
    return date("YmdHis",intval(time())).rand(1000,9999);
}


//官方函数修改
//bio.com/public/download/index?path=Report/op_atta/2017-12-06/151255050131380.pdf&name=123.pdf
function my_force_download($filename = '', $downname = '',$data = '', $set_mime = FALSE)
{
    if ($filename === '' OR $data === '')
    {
        return;
    }
    elseif ($data === NULL)
    {
        if ( ! @is_file($filename) OR ($filesize = @filesize($filename)) === FALSE)
        {
            return;
        }

        $filepath = $filename;
        $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
        $filename = end($filename);
    }
    else
    {
        $filesize = strlen($data);
    }

    // Set the default MIME type to send
    $mime = 'application/octet-stream';

    $x = explode('.', $filename);
    $extension = end($x);

    if ($set_mime === TRUE)
    {
        if (count($x) === 1 OR $extension === '')
        {
            /* If we're going to detect the MIME type,
             * we'll need a file extension.
             */
            return;
        }

        // Load the mime types
        $mimes =& get_mimes();

        // Only change the default MIME if we can find one
        if (isset($mimes[$extension]))
        {
            $mime = is_array($mimes[$extension]) ? $mimes[$extension][0] : $mimes[$extension];
        }
    }

    /* It was reported that browsers on Android 2.1 (and possibly older as well)
     * need to have the filename extension upper-cased in order to be able to
     * download it.
     *
     * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
     */
    if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT']))
    {
        $x[count($x) - 1] = strtoupper($extension);
        $filename = implode('.', $x);
    }

    if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE)
    {
        return;
    }

    // Clean output buffer
    if (ob_get_level() !== 0 && @ob_end_clean() === FALSE)
    {
        @ob_clean();
    }

    // Generate the server headers
    header('Content-Type: '.$mime);
    header('Content-Disposition: attachment; filename="'.$downname.'"');
    header('Content-Type: application/octet-stream; name=' . $downname);
    header('Expires: 0');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.$filesize);
    header('Cache-Control: private, no-transform, no-store, must-revalidate');

    // If we have raw data - just dump it
    if ($data !== NULL)
    {
        exit($data);
    }

    // Flush 1MB chunks of data
    while ( ! feof($fp) && ($data = fread($fp, 1048576)) !== FALSE)
    {
        echo $data;
    }

    fclose($fp);
    exit;
}

function get_page_config($total_rows,$limit,$cur_page){
    return [
        'base_url'=>'',
        'total_rows'=>$total_rows,
        'per_page'=>$limit,
        'cur_page'=>$cur_page,
        'enable_query_strings'=>false,
        'use_page_numbers'=>true,
        'num_links'=>2,
        'full_tag_open'=>'<div class="snail_page">',
        'full_tag_close'=>'</div>',
        'first_link'=>'首页',
        'first_tag_open'=>'<p>',
        'first_tag_close'=>'</p>',
        'last_link'=>'尾页',
        'last_tag_open'=>'<p>',
        'last_tag_close'=>'</p>',
        'next_tag_open'=>'<p>',
        'next_tag_close'=>'</p>',
        'next_link'=>'下一页',
        'prev_tag_open'=>'<p>',
        'prev_tag_close'=>'</p>',
        'prev_link'=>'上一页',
        'num_tag_open'=>'<span>',
        'num_tag_close'=>'</span>',
        'cur_tag_open'=>'<span class="active">',
        'cur_tag_close'=>'</span>',
    ];
}

/**
* 验证手机号是否正确
* @author honfei
* @param number $mobile
*/
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}

function explode_plus($str){
    $str = str_replace('，',';',$str);
    $str = str_replace(',',';',$str);
    $str = str_replace('；',';',$str);
    $arr = explode(';',$str);
    foreach($arr as &$item){
        $item = trim($item);
    }
    return $arr;
}

/**
 * [upload_image_to_oss 上传图片到阿里云OSS]
 * @param  [type] $img_path  [URL or path]
 * @param  [type] $save_path [description]
 * @return [type]            [description]
 */
function upload_image_to_oss($img_path,$save_path){
    require_once TOOLS_PATH . '/aliyunoss/AliyunOss.php';
    $aliyunOss = new AliyunOss();
    $aliyunOss::__instance();
    $tmurl = $aliyunOss::uploaded_file($img_path, $save_path);
    return $tmurl;
}

/**
 * [upload_file_to_oss 上传文件到阿里云OSS]
 * @param  [type] $file_path [URL OR PATH ]
 * @param  [type] $save_path [description]
 * @return [type]            [description]
 */
function upload_file_to_oss($file_path,$save_path){
    require_once TOOLS_PATH . '/aliyunoss/AliyunOss.php';
    $aliyunOss = new AliyunOss();
    $aliyunOss::__instance();
    $tmurl = $aliyunOss::uploaded_file($file_path, $save_path);
    return $tmurl;
}

/**
 * [check_remote_file_exists 判断远程文件是否存在]
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function check_remote_file_exists($url) 
{
    $curl = curl_init($url); // 不取回数据
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); // 发送请求
    $result = curl_exec($curl);
    $found = false; // 如果请求没有发送失败
    if ($result !== false) {
 
        /** 再检查http响应码是否为200 */
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode == 200) {
            $found = true;
        }
    }
    curl_close($curl);
 
    return $found;
}

/**
 * [get_search_aim 查询目标]
 * @return [type] [description]
 */
function get_search_aim()
{
    return [
        "项目申报（国家级、省部级、学协会、其他）",
        "中小企业创新基金（研发阶段、小试-中试、市场推广）",
        "新产品",
        "技术引进",
        "技术吸收与创新",
        "成果鉴定",
        "高新技术成果转化",
        "申报奖励（国家级、省部级、学协会、其他奖励）",
        "高新技术企业认定",
        "博士论文开题",
        "申报专利",
        "其他"
    ];
}

/**
 * [get_search_range 查询范围]
 * @return [type] [description]
 */
function get_search_range()
{
    return [
        "国内",
        "国内外"
    ];
}

/**
 * [get_subject_category 获取学科分类]
 * @return [type] [description]
 */
function get_subject_category()
{
    return [
        "信息科学与系统科学",
        "生物学（生物医药、生物技术）",
        "力学",
        "物理学",
        "化学",
        "数学",
        "天文学",
        "地球科学",
        "材料科学",
        "环境科学及资源科学技术",
        "动力与电气工程",
        "能源科学技术",
        "电子与通讯技术",
        "食品科学技术",
        "航空航天科学技术",
        "农业科学",
        "林学",
        "水产学",
        "其他科学"

    ];
}

/**
 * [get_industry_category 获取产业分类]
 * @return [type] [description]
 */
function get_industry_category()
{
    return [
        "节能环保产业（节水、节能、环保等）",
        "生物产业（生物医药、生物工程、生物农业等）",
        "新一代信息技术产业",
        "高端装备制造产业（卫星及服务、轨道交通、智能制造等）",
        "新能源产业（太阳能、风能、生物质能源等）",
        "新材料产业（新型功能材料、高分子、无机非金属、复合材料等）",
        "新能源汽车",
        "国民经济其他产业"

    ];
}
function my_strip_tags($str){
    $str = str_replace("<sub>","===",$str);
    $str = str_replace("</sub>","|||",$str);
    $str = strip_tags($str);
    $str = str_replace("===","<sub>",$str);
    $str = str_replace("|||","</sub>",$str);
    return $str;
}

