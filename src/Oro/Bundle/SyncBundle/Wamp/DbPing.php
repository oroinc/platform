<?php

namespace Oro\Bundle\SyncBundle\Wamp;

# TODO: should be uncommented in scope of BAP-16769, see https://github.com/laboro/dev/pull/16599/files for details
//use JDare\ClankBundle\Periodic\PeriodicInterface;
//
//class DbPing implements PeriodicInterface
//{
//    /**
//     * @var \PDO PDO instance.
//     */
//    private $pdo;
//
//    /**
//     * @param \PDO $pdo A \PDO instance, same as used by session handler
//     */
//    public function __construct(\PDO $pdo = null)
//    {
//        $this->pdo = $pdo;
//    }
//
//    /**
//     * This function is executed every 1 minute to make sure that WebSocket server has a DB connection.
//     */
//    public function tick()
//    {
//        if ($this->pdo instanceof \PDO) {
//            $stmt = $this->pdo->prepare("SELECT 1");
//
//            $stmt->execute();
//        }
//    }
//}
