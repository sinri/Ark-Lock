<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 21:46
 */

namespace sinri\ark\lock\mutex;


use Exception;
use sinri\ark\lock\util\Loop;
use sinri\ark\lock\util\PcntlTimeout;

class ArkFlockMutex extends ArkLockMutex
{
    const INFINITE_TIMEOUT = -1;
    /**
     * @internal
     */
    const STRATEGY_BLOCK = 1;
    /**
     * @internal
     */
    const STRATEGY_PCNTL = 2;
    /**
     * @internal
     */
    const STRATEGY_BUSY = 3;
    /**
     * @var resource $fileHandle The file handle.
     */
    private $fileHandle;
    /**
     * @var int
     */
    private $timeout;
    /**
     * @var int
     */
    private $strategy;

    /**
     * Sets the file handle.
     *
     * @param string $file The file handle.
     * @param int $timeout
     */
    public function __construct($file, $timeout = self::INFINITE_TIMEOUT)
    {
        $fileHandle = fopen($file, "w+");
        if (!is_resource($fileHandle)) {
            throw new \InvalidArgumentException("The file handle is not a valid resource.");
        }
        $this->fileHandle = $fileHandle;
        $this->timeout = $timeout;
        $this->strategy = $this->determineLockingStrategy();

        $this->setMutexName($file);
    }

    private function determineLockingStrategy()
    {
        if ($this->timeout == self::INFINITE_TIMEOUT) {
            return self::STRATEGY_BLOCK;
        }
        if (PcntlTimeout::isSupported()) {
            return self::STRATEGY_PCNTL;
        }
        return self::STRATEGY_BUSY;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function lock()
    {
        switch ($this->strategy) {
            case self::STRATEGY_BLOCK:
                $this->lockBlocking();
                return;
            case self::STRATEGY_PCNTL:
                $this->lockPcntl();
                return;
            case self::STRATEGY_BUSY:
                $this->lockBusy();
                return;
        }
        throw new \RuntimeException("Unknown strategy '{$this->strategy}'.'");
    }

    /**
     * @throws Exception
     * @return void
     */
    private function lockBlocking()
    {
        if (!flock($this->fileHandle, LOCK_EX)) {
            throw new Exception("Failed to lock the file.");
        }
    }

    /**
     * @throws Exception
     * @return void
     */
    private function lockPcntl()
    {
        $timeBox = new PcntlTimeout($this->timeout);
        try {
            $timeBox->timeBoxed(
                function () {
                    $this->lockBlocking();
                }
            );
        } catch (Exception $e) {
            throw new Exception("Timeout of {$this->timeout} seconds exceeded.");
        }
    }

    /**
     * @throws Exception
     */
    private function lockBusy()
    {
        $loop = new Loop($this->timeout);
        $loop->execute(function () use ($loop) {
            if ($this->acquireNonBlockingLock()) {
                $loop->end();
            }
        });
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function acquireNonBlockingLock()
    {
        if (!flock($this->fileHandle, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                /*
                 * Another process holds the lock.
                 */
                return false;
            }
            throw new Exception("Failed to lock the file.");
        }
        return true;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function unlock()
    {
        if (!flock($this->fileHandle, LOCK_UN)) {
            throw new Exception("Failed to unlock the file.");
        }
    }
}