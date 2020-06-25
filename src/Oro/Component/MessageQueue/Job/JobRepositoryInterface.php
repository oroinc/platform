<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Job entity repository interface.
 */
interface JobRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return Job|null
     */
    public function findJobById(int $id): ?Job;

    /**
     * @param string $ownerId
     * @param string $jobName
     *
     * @return Job|null
     */
    public function findRootJobByOwnerIdAndJobName(string $ownerId, string $jobName): ?Job;

    /**
     * Finds root non interrupted job by name and given statuses.
     *
     * @param string $jobName
     * @param array $statuses
     *
     * @return Job|null
     */
    public function findRootJobByJobNameAndStatuses(string $jobName, array $statuses): ?Job;

    /**
     * @param string $name
     * @param Job $rootJob
     *
     * @return Job|null
     */
    public function findChildJobByName(string $name, Job $rootJob): ?Job;

    /**
     * @param Job $rootJob
     *
     * @return array
     */
    public function getChildStatusesWithJobCountByRootJob(Job $rootJob): array;

    /**
     * Be warned that
     * In PGSQL function returns array of ids in DESC order, every id has integer type,
     * But in MYSQL it will be array of ids in ASC order, every id has string type
     *
     * @param Job $rootJob
     * @param string $status
     *
     * @return array
     */
    public function getChildJobIdsByRootJobAndStatus(Job $rootJob, string $status): array;

    /**
     * @return Job
     */
    public function createJob(): Job;
}
