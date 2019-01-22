<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-21
 * Time: 11:57
 */

namespace sinri\ark\lock\multimutex;

abstract class ArkAbstractMultiLock extends ArkAbstractMultiMutex
{
    /**
     * @var string[]
     */
    protected $lockStack = [];
    /**
     * @var \Exception
     */
    protected $lockException;
    /**
     * @var \Exception[]
     */
    protected $unlockExceptions = [];
    /**
     * @var \Exception
     */
    protected $executeException;
    /**
     * @var bool
     */
    protected $successLocking;

    /**
     * @return \Exception
     */
    public function getLockException()
    {
        return $this->lockException;
    }

    /**
     * @return \Exception[]
     */
    public function getUnlockExceptions()
    {
        return $this->unlockExceptions;
    }

    /**
     * @return \Exception
     */
    public function getExecuteException()
    {
        return $this->executeException;
    }

    /**
     * @return bool
     */
    public function isSuccessLocking()
    {
        return $this->successLocking;
    }

    /**
     * @param callable $code
     * @return mixed|null whatever the callable code returns, or null for not done
     */
    public function synchronized($code)
    {
        $result = null;
        $this->successLocking = true;
        if (!$this->pushAllLocks()) {
            $this->successLocking = false;
            return $result;
        }
        try {
            $result = call_user_func_array($code, []);
        } catch (\Exception $exception) {
            $this->executeException = $exception;
        } finally {
            if (!$this->popAllLocks()) {
                $this->successLocking = false;
            }
        }
        return $result;
    }

    /**
     * Lock, or throw fail exception
     * @return bool
     */
    final protected function pushAllLocks()
    {
        $mutexes = $this->getMutexes();
        try {
            foreach ($mutexes as $mutexName) {
                $this->lock($mutexName);
                array_push($this->lockStack, $mutexName);
            }
            return true;
        } catch (\Exception $exception) {
            $this->lockException = $exception;
            $this->popAllLocks();
            return false;
        }
    }

    abstract protected function lock($mutexName);

    /**
     * If all unlocked, return true
     * @return bool
     */
    final protected function popAllLocks()
    {
        while (!empty($this->lockStack)) {
            try {
                $mutexName = array_pop($this->lockStack);
                $this->unlock($mutexName);
            } catch (\Exception $exception) {
                $this->unlockExceptions[] = $exception;
            }
        }
        return empty($this->unlockExceptions);
    }

    abstract protected function unlock($mutexName);
}