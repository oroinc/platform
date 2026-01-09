<?php

declare(strict_types=1);

namespace Oro\Component\DoctrineUtils\ORM\Id;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

/**
 * PostgreSQL 13+ UUID generator using native gen_random_uuid() function
 */
class UuidGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity): string
    {
        $connection = $em->getConnection();

        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToPrimary();
        }

        $result = $connection->executeQuery('SELECT gen_random_uuid()::text')->fetchOne();

        if ($result === false || $result === null) {
            throw new \RuntimeException('Failed to generate UUID from PostgreSQL');
        }

        return (string) $result;
    }
}
