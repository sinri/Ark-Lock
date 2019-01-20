<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 22:19
 */

namespace sinri\ark\lock\mutex;


class ArkDummyMutex extends ArkAbstractMutex
{
    public function synchronized($code)
    {
        return call_user_func_array($code, []);
    }
}