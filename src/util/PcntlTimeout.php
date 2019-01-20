<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 22:01
 */

namespace sinri\ark\lock\util;


use Exception;

final class PcntlTimeout
{
    /**
     * @var int Timeout in seconds
     */
    private $timeout;

    /**
     * Builds the timeout.
     *
     * @param int $timeout Timeout in seconds
     */
    public function __construct($timeout)
    {
        if (!self::isSupported()) {
            throw new \RuntimeException("PCNTL module not enabled");
        }
        if ($timeout <= 0) {
            throw new \InvalidArgumentException("Timeout must be positive and non zero");
        }
        $this->timeout = $timeout;
    }

    /**
     * Runs the code and would eventually time out.
     *
     * This method has the side effect, that any signal handler
     * for SIGALRM will be reset to the default handler (SIG_DFL).
     * It also expects that there is no previously scheduled alarm.
     * If your application uses alarms ({@link pcntl_alarm()}) or
     * a signal handler for SIGALRM, don't use this method. It will
     * interfere with your application and lead to unexpected behaviour.
     *
     * @param callable $code Executed code block
     * @return mixed Return value of the executed block
     *
     * @throws Exception Running the code hit the deadline or Installing the timeout failed
     */
    public function timeBoxed($code)
    {
        $existingHandler = pcntl_signal_get_handler(SIGALRM);
        $signal = pcntl_signal(SIGALRM, function () {
            throw new Exception(sprintf("TimeBox hit deadline of %d seconds", $this->timeout));
        });
        if (!$signal) {
            throw new Exception("Could not install signal");
        }
        $oldAlarm = pcntl_alarm($this->timeout);
        if ($oldAlarm != 0) {
            throw new Exception("Existing alarm was not expected");
        }
        try {
            return call_user_func_array($code, []);
        } finally {
            pcntl_alarm(0);
            pcntl_signal_dispatch();
            pcntl_signal(SIGALRM, $existingHandler);
        }
    }

    /**
     * Returns if this class is supported by the PHP runtime.
     *
     * This class requires the pcntl module. This method checks if
     * it is available.
     *
     * @return bool TRUE if this class is supported by the PHP runtime.
     */
    public static function isSupported()
    {
        return
            PHP_SAPI === "cli" &&
            extension_loaded("pcntl") &&
            function_exists("pcntl_alarm") &&
            function_exists("pcntl_signal") &&
            function_exists("pcntl_signal_dispatch");
    }
}