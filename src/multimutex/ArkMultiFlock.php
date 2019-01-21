<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-22
 * Time: 00:11
 */

namespace sinri\ark\lock\multimutex;


use Exception;
use sinri\ark\lock\util\Loop;
use sinri\ark\lock\util\PcntlTimeout;

class ArkMultiFlock extends ArkAbstractMultiLock
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
     * @var resource[] $fileHandles The file handle.
     */
    private $fileHandles;
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
     * @param string[] $files The file handle.
     * @param int $timeout
     */
    public function __construct($files, $timeout = self::INFINITE_TIMEOUT)
    {
        $this->timeout = $timeout;
        $this->strategy = $this->determineLockingStrategy();

        $this->setMutexes($files);

        foreach ($files as $file) {
            $this->fileHandles[$file] = fopen($file, "w+");
        }
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
     * @param $mutexName
     * @return void
     * @throws Exception
     */
    protected function lock($mutexName)
    {
        switch ($this->strategy) {
            case self::STRATEGY_BLOCK:
                $this->lockBlocking($mutexName);
                return;
            case self::STRATEGY_PCNTL:
                $this->lockPcntl($mutexName);
                return;
            case self::STRATEGY_BUSY:
                $this->lockBusy($mutexName);
                return;
        }
        throw new \RuntimeException("Unknown strategy '{$this->strategy}'.'");
    }

    /**
     * @param $mutexName
     * @return void
     * @throws Exception
     */
    private function lockBlocking($mutexName)
    {
        if (!flock($this->fileHandles[$mutexName], LOCK_EX)) {
            throw new Exception("Failed to lock the file.");
        }
    }

    /**
     * @param $mutexName
     * @return void
     * @throws Exception
     */
    private function lockPcntl($mutexName)
    {
        $timeBox = new PcntlTimeout($this->timeout);
        try {
            $timeBox->timeBoxed(
                function () use ($mutexName) {
                    $this->lockBlocking($mutexName);
                }
            );
        } catch (Exception $e) {
            throw new Exception("Timeout of {$this->timeout} seconds exceeded.");
        }
    }

    /**
     * @param $mutexName
     * @throws Exception
     */
    private function lockBusy($mutexName)
    {
        $loop = new Loop($this->timeout);
        $loop->execute(function () use ($mutexName, $loop) {
            if ($this->acquireNonBlockingLock($mutexName)) {
                $loop->end();
            }
        });
    }

    /**
     * @param $mutexName
     * @return bool
     * @throws Exception
     */
    private function acquireNonBlockingLock($mutexName)
    {
        if (!flock($this->fileHandles[$mutexName], LOCK_EX | LOCK_NB, $wouldBlock)) {
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
     * @param $mutexName
     * @return void
     * @throws Exception
     */
    protected function unlock($mutexName)
    {
        if (!flock($this->fileHandles[$mutexName], LOCK_UN)) {
            throw new Exception("Failed to unlock the file.");
        }
    }

    /**
     * This might be called after use
     */
    public function closeAllFiles()
    {
        foreach ($this->fileHandles as $fileHandle) {
            try {
                fclose($fileHandle);
            } catch (Exception $exception) {
                //
            }
        }
    }
}