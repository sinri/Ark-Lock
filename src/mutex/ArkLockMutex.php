<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 21:06
 */

namespace sinri\ark\lock\mutex;


use Exception;

abstract class ArkLockMutex extends ArkAbstractMutex
{
    /**
     * @var bool
     */
    protected $successLocking;
    /**
     * @var Exception
     */
    protected $exceptionToLock;
    /**
     * @var Exception
     */
    protected $exceptionToExecute;
    /**
     * @var Exception
     */
    protected $exceptionToUnlock;

    /**
     * @return bool
     */
    public function isSuccessLocking()
    {
        return $this->successLocking;
    }

    /**
     * @return Exception
     */
    public function getExceptionToLock()
    {
        return $this->exceptionToLock;
    }

    /**
     * @return Exception
     */
    public function getExceptionToExecute()
    {
        return $this->exceptionToExecute;
    }

    /**
     * @return Exception
     */
    public function getExceptionToUnlock()
    {
        return $this->exceptionToUnlock;
    }

    /**
     * @param callable $code
     * @return mixed|null
     */
    public function synchronized($code)
    {
        $this->successLocking = true;
        $this->exceptionToLock = null;
        $this->exceptionToExecute = null;
        $this->exceptionToUnlock = null;

        $code_result = null;

        try {
            $this->lock();
            try {
                $code_result = call_user_func_array($code, []);
            } catch (Exception $exception) {
                $this->exceptionToExecute = $exception;
            } finally {
                try {
                    $this->unlock();
                } catch (Exception $exception) {
                    $this->exceptionToUnlock = $exception;
                    $this->successLocking = false;
                }
            }
        } catch (Exception $exception) {
            $this->exceptionToLock = $exception;
            $this->successLocking = false;
        }
        return $code_result;
    }

    /**
     * Acquires the lock.
     *
     * This method blocks until the lock was acquired.
     *
     * @throws Exception The lock could not be acquired.
     * @return void
     */
    abstract protected function lock();

    /**
     * Releases the lock.
     *
     * @throws Exception The lock could not be released.
     * @return void
     */
    abstract protected function unlock();

}