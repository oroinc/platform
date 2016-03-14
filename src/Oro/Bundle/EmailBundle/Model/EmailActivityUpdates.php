<?php

namespace Oro\Bundle\EmailBundle\Model;

use Doctrine\Common\Util\ClassUtils;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EmailActivityUpdates
{
    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var object[] */
    protected $possibleEntitiesOwnedByEmails = [];

    /**
     * @param EmailOwnersProvider $emailOwnersProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EmailOwnersProvider $emailOwnersProvider, DoctrineHelper $doctrineHelper)
    {
        $this->emailOwnersProvider = $emailOwnersProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param object[] $entities
     */
    public function processCreatedEntities(array $entities)
    {
        $this->possibleEntitiesOwnedByEmails = array_merge(
            $this->possibleEntitiesOwnedByEmails,
            $entities
        );
    }

    /**
     * @return Job[]
     */
    public function createJobs()
    {
        $entities = $this->filterEntitiesToUpdate();
        $this->clearPendingEntities();
        $jobsArgs = $this->createJobsArgs($entities);

        return $this->createJobEntities($jobsArgs);
    }

    /**
     * @return object[]
     */
    protected function filterEntitiesToUpdate()
    {
        return array_filter(
            $this->possibleEntitiesOwnedByEmails,
            function ($entity) {
                return $this->emailOwnersProvider->hasEmailsByOwnerEntity($entity);
            }
        );
    }

    protected function clearPendingEntities()
    {
        $this->possibleEntitiesOwnedByEmails = [];
    }

    /**
     * @param object[] $entitiesOwnedByEmails
     *
     * @return array
     */
    protected function createJobsArgs(array $entitiesOwnedByEmails)
    {
        return array_reduce(
            $entitiesOwnedByEmails,
            function ($jobsArgsByClass, $emailOwner) {
                $class = ClassUtils::getClass($emailOwner);
                if (!isset($jobsArgsByClass[$class])) {
                    $jobsArgsByClass[$class] = [$class];
                }

                $jobsArgsByClass[$class][] = $this->doctrineHelper->getSingleEntityIdentifier($emailOwner);

                return $jobsArgsByClass;
            },
            []
        );
    }

    /**
     * @param array $jobsArgs
     *
     * @return Job[]
     */
    protected function createJobEntities(array $jobsArgs)
    {
        return array_map(
            function ($jobArgs) {
                return new Job('oro:email:update-email-owner-associations', $jobArgs);
            },
            $jobsArgs
        );
    }
}
