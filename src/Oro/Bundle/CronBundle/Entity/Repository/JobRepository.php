<?php

/**
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @TODO
 * Remove this file after BAP-10703 implementation or
 * after migration from jms/job-queue-bundle 1.2.* to jms/job-queue-bundle 1.3.*
 *
 * This fix brings performance optimization of JobRepository which was introduced in
 * jms/job-queue-bundle 1.3.0. As of there are other stories to upgrade jms/job-queue-bundle version
 * or replace it, this solution is temporary.
 */
namespace Oro\Bundle\CronBundle\Entity\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;

use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\Entity\Repository\JobRepository as JMSJobRepository;

class JobRepository extends JMSJobRepository
{
    /** @var \ReflectionMethod */
    protected $closeJobMethod;

    /**
     * These jobs were closed, but cannot be detached since there are jobs which have the job in dependencies
     * (Job::dependencies)
     *
     * Detaching such job would cause doctrine thinking that the jobs in dependencies are new entities and exception
     * would be thrown once the job would be updated.
     *
     * @var Job[]
     */
    protected $jobsPendingDetach = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $classRef = new \ReflectionClass(JMSJobRepository::class);
        $this->closeJobMethod = $classRef->getMethod('closeJobInternal');
        $this->closeJobMethod->setAccessible(true);
    }

    /**
     * {@inheritdoc}
     *
     * This method fixes memory leak in original implementation which causes that Jobs are not detached,
     * therefore memory consumption slowly increases till the crash once it hits memory limit.
     *
     * @see https://github.com/schmittjoh/JMSJobQueueBundle/issues/146
     */
    public function closeJob(Job $job, $finalState)
    {
        $this->_em->getConnection()->beginTransaction();
        try {
            $visited = array();
            $this->closeJobMethod->invokeArgs($this, [$job, $finalState, &$visited]);
            $this->_em->flush();
            $this->_em->getConnection()->commit();

            // Clean-up entity manager to allow for garbage collection to kick in.
            foreach ($visited as $job) {
                // If the job is an original job which is now being retried, let's
                // not remove it just yet.
                if ($job->isClosedNonSuccessful() && $job->isRetryJob()) {
                    continue;
                }

                if ($this->jobCanBeSafelyDetached($job)) {
                    $this->_em->detach($job);
                } else {
                    $this->jobsPendingDetach[spl_object_hash($job)] = $job;
                }
            }
        } catch (\Exception $ex) {
            $this->_em->getConnection()->rollback();

            throw $ex;
        }

        $this->detachJobsPendingDetach();
    }

    /**
     * @param Job $job
     *
     * @return Job[]
     */
    public function findIncomingDependencies(Job $job)
    {
        $jobIds = $this->getJobIdsOfIncomingDependencies($job);
        if (empty($jobIds)) {
            return [];
        }
        $query = "SELECT j, d FROM JMSJobQueueBundle:Job j LEFT JOIN j.dependencies d WHERE j.id IN (:ids)";
        return $this->_em->createQuery($query)
            ->setParameter('ids', $jobIds)
            ->getResult();
    }

    /**
     * @param Job $job
     *
     * @return Job[]
     */
    public function getIncomingDependencies(Job $job)
    {
        $jobIds = $this->getJobIdsOfIncomingDependencies($job);
        if (empty($jobIds)) {
            return [];
        }
        return $this->_em->createQuery("SELECT j FROM JMSJobQueueBundle:Job j WHERE j.id IN (:ids)")
            ->setParameter('ids', $jobIds)
            ->getResult();
    }

    /**
     * @param Job $job
     *
     * @return array
     */
    protected function getJobIdsOfIncomingDependencies(Job $job)
    {
        $query = "SELECT source_job_id FROM jms_job_dependencies WHERE dest_job_id = :id";
        $jobIds = $this->_em->getConnection()
            ->executeQuery($query, ['id' => $job->getId()])
            ->fetchAll(\PDO::FETCH_COLUMN);
        return $jobIds;
    }

    protected function detachJobsPendingDetach()
    {
        foreach ($this->jobsPendingDetach as $k => $job) {
            if ($this->jobCanBeSafelyDetached($job)) {
                $this->_em->detach($job);
                unset($this->jobsPendingDetach[$k]);
            }
        }
    }

    /**
     * Checks if job can be safely detached
     * (is not in Job::dependencies of another job)
     *
     * @param Job $job
     *
     * @return boolean
     */
    protected function jobCanBeSafelyDetached(Job $job)
    {
        $uow = $this->_em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();
        $managedJobs = isset($identityMap[Job::class]) ? $identityMap[Job::class] : [];
        foreach ($managedJobs as $managedJob) {
            if ($managedJob !== $job && $managedJob->hasDependency($job)) {
                return false;
            }
        }

        return true;
    }
}
