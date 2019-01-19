<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 22:19
 */

namespace sinri\ark\lock\mutex;


use Mutex;

class ArkDummyMutex extends Mutex
{
    public function synchronized(callable $code)
    {
        return call_user_func_array($code, []);
    }
}