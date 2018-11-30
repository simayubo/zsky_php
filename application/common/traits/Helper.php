<?php

namespace app\common\traits;

use Exception;
use think\facade\Log;
use think\facade\Response;
use WebSocket\Client;

trait Helper{

    /**
     * 响应输出
     * @param $success
     * @param $msg
     * @param array $data
     * @param int $code
     * @param int $http_code
     */
    public static function response($success, $msg, $data = [], $code = 0, $http_code = 200){
        if ($success){
            $sucstr = 'success';
        }else{
            $sucstr = 'fail';
        }
        $result = [
            'status' => $sucstr,
            'msg' => $msg,
            'code' => $code,
            'data' => $data,
        ];

//        Log::info('前台输出：' . json_encode($result));

        $response = Response::create($result, 'json', $http_code);
        return $response->send();
    }

    /**
     * Curl 请求
     * @param $url
     * @param string $method
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public static function curlResponse($url, $method = 'get', $data = [], $header = [])
    {
        //初始化
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (strtolower($method) == 'post'){
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置post数据
        $post_data = $data;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        //关闭URL请求
        curl_close($curl);
        if ($httpCode != 200){
            throw new Exception('请求失败-'.$httpCode, $httpCode);
        }

        return $result;
    }

    /**
     * 打印
     * @param $data
     */
    public static function dump($data){
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }

    /**
     * 打印(截断程序)
     * @param $data
     */
    public static function dd($data){
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
        exit();
    }
}