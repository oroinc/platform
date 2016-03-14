<?php

namespace Oro\Bundle\EmailBundle\Model;

use Doctrine\Common\Util\ClassUtils;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class EmailActivityUpdates
{
    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var EmailAddress[] */
    protected $updatedEmailAddresses = [];

    /**
     * @param EmailOwnersProvider $emailOwnersProvider
     */
    public function __construct(EmailOwnersProvider $emailOwnersProvider)
    {
        $this->emailOwnersProvider = $emailOwnersProvider;
    }

    /**
     * @param EmailAddress[] $emailAddresses
     */
    public function processUpdatedEmailAddresses(array $emailAddresses)
    {
        $this->updatedEmailAddresses = array_merge(
            $this->updatedEmailAddresses,
            $emailAddresses
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
     * @return EmailOwnerInterface[]
     */
    protected function filterEntitiesToUpdate()
    {
        $owners = array_map(
            function (EmailAddress $emailAddress) {
                return $emailAddress->getOwner();
            },
            $this->updatedEmailAddresses
        );

        return array_filter(
            $owners,
            function (EmailOwnerInterface $owner = null) {
                return $owner && $this->emailOwnersProvider->hasEmailsByOwnerEntity($owner);
            }
        );
    }

    protected function clearPendingEntities()
    {
        $this->updatedEmailAddresses = [];
    }

    /**
     * @param EmailOwnerInterface[] $entitiesOwnedByEmails
     *
     * @return array
     */
    protected function createJobsArgs(array $entitiesOwnedByEmails)
    {
        return array_reduce(
            $entitiesOwnedByEmails,
            function ($jobsArgsByClass, EmailOwnerInterface $emailOwner) {
                $class = ClassUtils::getClass($emailOwner);
                if (!isset($jobsArgsByClass[$class])) {
                    $jobsArgsByClass[$class] = [$class];
                }

                $jobsArgsByClass[$class][] = $emailOwner->getId();

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
