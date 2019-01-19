<?php
/**
 * Created by PhpStorm.
 * User: sinri
 * Date: 2019-01-19
 * Time: 23:01
 */

namespace sinri\ark\lock\mutex;


use Exception;

class ArkMySQLMutex extends ArkLockMutex
{

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $timeout;

    /**
     * ArkMySQLMutex constructor.
     * @param \PDO $PDO
     * @param string $name
     * @param int $timeout
     */
    public function __construct($PDO, $name, $timeout = 0)
    {
        $this->pdo = $PDO;

        if (\strlen($name) > 64) {
            throw new \InvalidArgumentException("The maximum length of the lock name is 64 characters.");
        }

        $this->name = $name;
        $this->timeout = $timeout;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function lock()
    {
        $statement = $this->pdo->prepare("SELECT GET_LOCK(?,?)");

        $statement->execute([
            $this->name,
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
            throw new Exception("An error occurred while acquiring the lock");
        }

        throw new Exception("Timeout of {$this->timeout} seconds exceeded.");
    }

    /**
     * @return void
     */
    public function unlock()
    {
        $statement = $this->pdo->prepare("DO RELEASE_LOCK(?)");
        $statement->execute([
            $this->name
        ]);
    }
}