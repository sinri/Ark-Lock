<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 19:54
 */

namespace sinri\ark\lock\mutex;


use sinri\ark\lock\trust\ArkMutexTrust;

abstract class ArkAbstractMutex
{
    /**
     * @var string
     */
    protected $mutexName;

    /**
     * @return string
     */
    public final function getMutexName()
    {
        return $this->mutexName;
    }

    /**
     * @param string $mutexName
     */
    public final function setMutexName($mutexName)
    {
        $this->mutexName = $mutexName;
    }

    /**
     * Executes the given callback exclusively.
     *
     * @param callable $code The synchronized execution block.
     * @return mixed The return value of the execution block.
     *
     * @throws \Exception The execution block threw an exception.
     */
    abstract public function synchronized($code);

    /**
     * Rely on a TRUST to performs a double-check before execution.
     *
     * Call {@link ArkMutexTrust::then()} on the returned object.
     *
     * Example:
     * <code>
     * $mutex->check(function ($check_type) use ($anything) {
     *     return $anything===true;
     * })->then(function ($check_type) use ($anotherThing) {
     *     return $anotherThing->execute();
     * });
     * </code>
     *
     * @param callable $check should return boolean, when it return false, Exception would be thrown
     * @return ArkMutexTrust The double-checked locking pattern.
     */
    public function check($check)
    {
        return new ArkMutexTrust($this, $check);
    }
}