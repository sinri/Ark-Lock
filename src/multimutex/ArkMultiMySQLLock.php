<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-22
 * Time: 00:23
 */

namespace sinri\ark\lock\multimutex;


use Exception;

class ArkMultiMySQLLock extends ArkAbstractMultiLock
{

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var int
     */
    private $timeout;

    /**
     * ArkMySQLMutex constructor.
     * @param \PDO $PDO
     * @param string[] $names
     * @param int $timeout
     */
    public function __construct($PDO, $names, $timeout = 5)
    {
        $this->pdo = $PDO;

        foreach ($names as $name) {
            if (strlen($name) > 64) {
                throw new \InvalidArgumentException("The maximum length of the lock name is 64 characters.");
            }
        }

        $this->mutexes = $names;
        $this->timeout = $timeout;
    }

    /**
     * @param $mutexName
     * @return void
     * @throws Exception
     */
    public function lock($mutexName)
    {
        $statement = $this->pdo->prepare("SELECT GET_LOCK(?,?)");

        $statement->execute([
            $mutexName,
            $this->timeout,
        ]);

        $statement->setFetchMode(\PDO::FETCH_NUM);
        $row = $statement->fetch();

        if ($row[0] == 1) {
            /*
             * Returns 1 if the lock was obtained successfully.
             */
            return;
        }

        if ($row[0] === null) {
            /*
             *  NULL if an error occurred (such as running out of memory or the thread was killed with mysqladmin kill).
             */
            throw new Exception("An error occurred while acquiring the lock of $mutexName");
        }

        throw new Exception("Timeout of {$this->timeout} seconds exceeded for lock $mutexName.");
    }

    /**
     * @param $mutexName
     * @return void
     */
    public function unlock($mutexName)
    {
        $statement = $this->pdo->prepare("DO RELEASE_LOCK(?)");
        $statement->execute([
            $mutexName
        ]);
    }
}