<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 22:19
 */

namespace sinri\ark\lock\mutex;


use Exception;

class ArkDummyMutex extends ArkLockMutex
{
    public function synchronized($code)
    {
        return call_user_func_array($code, []);
    }

    /**
     * Acquires the lock.
     *
     * This method blocks until the lock was acquired.
     *
     * @throws Exception The lock could not be acquired.
     * @return void
     */
    public function lock()
    {
        // do nothing
    }

    /**
     * Releases the lock.
     *
     * @throws Exception The lock could not be released.
     * @return void
     */
    public function unlock()
    {
        // do nothing
    }
}