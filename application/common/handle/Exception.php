<?php

namespace app\common\handle;

use Exception as Exp;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;

class Exception extends Handle
{
    public function render(Exp $e)
    {
        if (request()->module() == 'api'){
            $data = [
                'code' => -1,
                'msg' => $e->getMessage(),
                'data' => [
                    'err_file' => $e->getFile(),
                    'err_line' => $e->getLine(),
                    'err_trace' => $e->getTrace()
                ],
            ];
            if ($e instanceof ValidateException) {
                return response($data, 422, [], 'json');
            }
            // 请求异常
            if ($e instanceof HttpException) {
                return response($data, $e->getStatusCode(), [], 'json');
            }

            return response($data, 500, [], 'json');
        }else{
            return parent::render($e);
        }
    }

}