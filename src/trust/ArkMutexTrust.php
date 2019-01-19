<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 20:04
 */

namespace sinri\ark\lock\trust;


use mysql_xdevapi\Exception;
use sinri\ark\lock\mutex\ArkAbstractMutex;

class ArkMutexTrust
{
    const FIRST_CHECK = 1;
    const SECOND_CHECK = 2;

    /**
     * @var ArkAbstractMutex The mutex.
     */
    private $mutex;

    /**
     * @var callable The check.
     */
    private $check;

    /**
     * ArkMutexTrust constructor.
     * @param ArkAbstractMutex $mutex
     * @param callable $check the return value should be boolean.
     */
    public function __construct($mutex, $check)
    {
        $this->mutex = $mutex;
        $this->check = $check;
    }

    /**
     * Executes a callback only if a check is true, async and sync.
     *
     * Both the check and the code execution are locked by a mutex.
     * Only if the check fails the method returns before acquiring a lock.
     *
     * 1. Run check for first time (if return false, throw Exception);
     * 2. Open synchronized mode
     * 2.1. Run check for second time (if return false, throw Exception);
     * 2.2. Run the actual synchronized code
     *
     * @param  callable $code The locked code.
     * @return mixed what ever the synchronized code returns.
     *
     * @throws \Exception The execution block or the check threw an exception.
     */
    public function then($code)
    {
        if (!call_user_func_array($this->check, [self::FIRST_CHECK])) {
            throw new Exception("ArkMutexTrust First Check Failed for Mutex [" . $this->mutex->getMutexName() . "]");
        }
        return $this->mutex->synchronized(function () use ($code) {
            if (!call_user_func_array($this->check, [self::SECOND_CHECK])) {
                throw new Exception("ArkMutexTrust Second Check Failed for Mutex [" . $this->mutex->getMutexName() . "]");
            }
            return $code();
        });
    }

}