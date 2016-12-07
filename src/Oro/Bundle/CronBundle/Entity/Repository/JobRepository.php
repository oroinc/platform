<?php

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
