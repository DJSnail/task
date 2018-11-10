<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if(ENVIRONMENT == 'production'){
    $host = '127.0.0.1';
    $port = '6379';
}else{
    $host = '10.32.33.42';
    //$host = '127.0.0.1';
    $port = '6379';
}

$redis = array(
    'host'=>$host,
    'port'=>$port,
);
$config['redis'] = $redis;