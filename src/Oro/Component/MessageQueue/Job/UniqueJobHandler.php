<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Handle unique table rows for jobs
 *
 * @internal
 */
class UniqueJobHandler
{
    /** @var bool */
    private $upsertSupport;

    /** @var bool */
    private $preSelectSupport;

    /** @var string */
    private $uniqueTableName;

    public function __construct(string $uniqueTableName)
    {
        $this->uniqueTableName = $uniqueTableName;
    }

    public function insert(Connection $connection, Job $job): void
    {
        $this->preSelectCheck($connection, $job);

        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform instanceof PostgreSqlPlatform && $this->getUpsertSupport($connection, '9.5')) {
            $this->runPostgreSqlPlatformJobs($connection, $job);

            return;
        } elseif ($databasePlatform instanceof MySqlPlatform && $this->getUpsertSupport($connection, '5.5')) {
            $this->runMySqlPlatformJobs($connection, $job);

            return;
        }

        try {
            $connection->insert($this->uniqueTableName, ['name' => $job->getOwnerId()], ['name' => 'string']);
            if ($job->isUnique()) {
                $connection->insert($this->uniqueTableName, ['name' => $job->getName()], ['name' => 'string']);
            }
        } catch (UniqueConstraintViolationException $e) {
            $this->throwException($job, true);
        }
    }

    public function delete(Connection $connection, Job $job): void
    {
        $connection->delete($this->uniqueTableName, ['name' => $job->getOwnerId()]);
        if ($job->isUnique()) {
            $connection->delete($this->uniqueTableName, ['name' => $job->getName()]);
        }
    }

    /**
     * @param bool $upsertSupport
     */
    public function setUpsertSupport(bool $upsertSupport): void
    {
        $this->upsertSupport = $upsertSupport;
    }

    /**
     * @param bool $preSelectSupport
     */
    public function setPreSelectSupport(bool $preSelectSupport): void
    {
        $this->preSelectSupport = $preSelectSupport;
    }

    private function preSelectCheck(Connection $connection, Job $job): void
    {
        if (!$this->preSelectSupport) {
            return;
        }

        $query = QueryBuilderUtil::sprintf(
            'SELECT 1 FROM %s WHERE name = :ownerId OR name = :name',
            $this->uniqueTableName
        );
        $select = $connection->fetchColumn($query, ['ownerId' => $job->getOwnerId(), 'name' => $job->getName()]);

        $this->throwException($job, $select);
    }

    private function runPostgreSqlPlatformJobs(Connection $connection, Job $job): void
    {
        $query = QueryBuilderUtil::sprintf(
            'INSERT INTO %s(name) VALUES(:name) ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name 
                RETURNING xmax::text::bigint > 0;',
            $this->uniqueTableName
        );
        $isDuplicate = $connection->fetchColumn($query, ['name' => $job->getOwnerId()], 0, ['name' => 'string']);
        $this->throwException($job, $isDuplicate);

        if (!$job->isUnique()) {
            return;
        }

        $isDuplicate = $connection->fetchColumn($query, ['name' => $job->getName()], 0, ['name' => 'string']);
        $this->throwException($job, $isDuplicate);
    }

    private function runMySqlPlatformJobs(Connection $connection, Job $job): void
    {
        $query = QueryBuilderUtil::sprintf('INSERT IGNORE INTO %s(name) VALUES(:name);', $this->uniqueTableName);
        $isUnique = $connection->executeUpdate($query, ['name' => $job->getOwnerId()], ['name' => 'string']);
        $this->throwException($job, !$isUnique);

        if (!$job->isUnique()) {
            return;
        }

        $isUnique = $connection->executeUpdate($query, ['name' => $job->getName()], ['name' => 'string']);
        $this->throwException($job, !$isUnique);
    }

    private function throwException(Job $job, bool $isDuplicate): void
    {
        if (!$isDuplicate) {
            return;
        }

        throw new DuplicateJobException(
            sprintf(
                'Duplicate job. ownerId:"%s", name:"%s"',
                $job->getOwnerId(),
                $job->getName()
            )
        );
    }

    private function getUpsertSupport(Connection $connection, string $requiredVersion): bool
    {
        if ($this->upsertSupport !== null) {
            return $this->upsertSupport;
        }

        $realVersion = $this->getServerVersion($connection);

        $this->upsertSupport = (bool)version_compare($realVersion, $requiredVersion, '>=');

        return $this->upsertSupport;
    }

    private function getServerVersion(Connection $connection): ?string
    {
        $params = $connection->getParams();
        if (isset($params['serverVersion'])) {
            return $params['serverVersion'];
        }

        $wrappedConnection = $connection->getWrappedConnection();
        if ($wrappedConnection instanceof ServerInfoAwareConnection) {
            return $wrappedConnection->getServerVersion();
        }

        if ($wrappedConnection instanceof \PDO) {
            return $wrappedConnection->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }

        return null;
    }
}
