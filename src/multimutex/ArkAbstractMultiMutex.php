<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-21
 * Time: 11:48
 */

namespace sinri\ark\lock\multimutex;


abstract class ArkAbstractMultiMutex
{
    /**
     * @var string[]
     */
    protected $mutexes;

    /**
     * @return string[]
     */
    public function getMutexes()
    {
        return $this->mutexes;
    }

    /**
     * @param string[] $mutexes
     */
    public function setMutexes($mutexes)
    {
        $this->mutexes = $mutexes;
    }

    /**
     * @param callable $code
     * @return mixed
     */
    abstract public function synchronized($code);
}