<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Handle unique table rows for jobs
 *
 * @internal
 */
class UniqueJobHandler
{
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

    public function list(Connection $connection): array
    {
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder->select('name')->from($this->uniqueTableName);

        return $queryBuilder->execute()->fetchFirstColumn();
    }

    public function setPreSelectSupport(bool $preSelectSupport): void
    {
        $this->preSelectSupport = $preSelectSupport;
    }

    public function checkRootJobOnDuplicate(Connection $connection, Job $job): void
    {
        $this->preSelectCheck($connection, $job);

        $query = QueryBuilderUtil::sprintf('SELECT name FROM %s WHERE name = :name;', $this->uniqueTableName);

        $isDuplicate = $connection->fetchColumn($query, ['name' => $job->getOwnerId()], 0, ['name' => 'string']);
        $this->throwException($job, $isDuplicate);

        if (!$job->isUnique()) {
            return;
        }

        $isDuplicate = $connection->fetchColumn($query, ['name' => $job->getName()], 0, ['name' => 'string']);
        $this->throwException($job, $isDuplicate);
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
}
