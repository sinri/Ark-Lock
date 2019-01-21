<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-22
 * Time: 00:42
 */

require_once __DIR__ . '/../vendor/autoload.php';

clear();

$lockFilePath_1 = __DIR__ . '/../debug/lock-file-1';
$lockFilePath_2 = __DIR__ . '/../debug/lock-file-2';

for ($i = 0; $i < 5; $i++) {
    $pid = pcntl_fork();
    if ($pid < 0) {
        echo "FORK ERROR!" . PHP_EOL;
        break;
    } elseif ($pid > 0) {
        // parent
        echo "FORKED " . $pid . PHP_EOL;
    } else {
        // child
        $myPid = getmypid();
        echo "JOB [$i][$myPid] STARTED" . PHP_EOL;
        $lock = new \sinri\ark\lock\multimutex\ArkMultiFlock([$lockFilePath_1, $lockFilePath_2]);
        $lock->synchronized(function () {
            $myPid = getmypid();
            add();
            $sleepTime = rand(1, 10);
            echo time() . "[$myPid] SLEEP $sleepTime SECONDS" . PHP_EOL;
            sleep($sleepTime);
            echo time() . "[$myPid] now " . read() . PHP_EOL;
        });
        echo time() . "[$myPid] success[$i]=" . json_encode($lock->isSuccessLocking()) . PHP_EOL;
        if (!$lock->isSuccessLocking()) {
            if ($lock->getExceptionToLock())
                echo time() . "[$myPid] getExceptionToLock " . $lock->getExceptionToLock()->getMessage() . PHP_EOL;
            if ($lock->getExceptionToExecute())
                echo time() . "[$myPid] getExceptionToExecute " . $lock->getExceptionToExecute()->getMessage() . PHP_EOL;
            if ($lock->getExceptionToUnlock())
                echo time() . "[$myPid] getExceptionToUnlock " . $lock->getExceptionToUnlock()->getMessage() . PHP_EOL;
        }
        exit;
    }
}

for (; $i > 0; $i--) {
    $waited = pcntl_wait($status);
    echo "WAITED " . json_encode($waited) . " -> " . json_encode($status) . PHP_EOL;
}

echo "FIN=" . read() . PHP_EOL;

function clear()
{
    $sharedFilePath = __DIR__ . '/../debug/shared-file';
    @unlink($sharedFilePath);
}

function add()
{
    $sharedFilePath = __DIR__ . '/../debug/shared-file';
    if (!file_exists($sharedFilePath)) {
        file_put_contents($sharedFilePath, 1);
    } else {
        $v = file_get_contents($sharedFilePath);
        file_put_contents($sharedFilePath, $v + 1);
    }
}

function read()
{
    $sharedFilePath = __DIR__ . '/../debug/shared-file';
    if (!file_exists($sharedFilePath)) {
        return 0;
    } else {
        return file_get_contents($sharedFilePath);
    }
}