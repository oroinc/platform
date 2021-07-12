<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

/**
 * This ORM executor prevents hiding an exception that is happened during executing a data fixture
 * in case the entity manager cannot be closed or the database transaction rollback failed.
 */
class DataFixturesORMExecutor extends ORMExecutor
{
    /**
     * {@inheritDoc}
     */
    public function execute(array $fixtures, $append = false)
    {
        $em = $this->getObjectManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            if ($append === false) {
                $this->purge();
            }

            foreach ($fixtures as $fixture) {
                $this->load($em, $fixture);
            }

            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            try {
                $em->close();
                $connection->rollBack();
            } catch (\Throwable $rollbackException) {
                // ignore any exceptions here to prevent hiding the original exception
            }

            throw $e;
        }
    }
}
