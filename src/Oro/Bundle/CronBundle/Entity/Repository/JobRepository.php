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

                $this->_em->detach($job);
            }
        } catch (\Exception $ex) {
            $this->_em->getConnection()->rollback();

            throw $ex;
        }
    }
}
