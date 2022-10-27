<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Job entity repository interface.
 */
interface JobRepositoryInterface
{
    public function findJobById(int $id): ?Job;

    public function findRootJobByOwnerIdAndJobName(string $ownerId, string $jobName): ?Job;

    /**
     * Finds root non interrupted job by name and given statuses.
     */
    public function findRootJobByJobNameAndStatuses(string $jobName, array $statuses): ?Job;

    public function findChildJobByName(string $name, Job $rootJob): ?Job;

    public function getChildStatusesWithJobCountByRootJob(Job $rootJob): array;

    /**
     * Be warned that
     * In PGSQL function returns array of ids in DESC order, every id has integer type,
     * But in MYSQL it will be array of ids in ASC order, every id has string type
     */
    public function getChildJobIdsByRootJobAndStatus(Job $rootJob, string $status): array;

    public function createJob(): Job;
}
