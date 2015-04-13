<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;

class EmailActivityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param ActivityManager $activityManager
     */
    public function __construct(ActivityManager $activityManager)
    {
        $this->activityManager = $activityManager;
    }

    /**
     * Handle onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $newEntities = $uow->getScheduledEntityInsertions();
        foreach ($newEntities as $entity) {
            if ($entity instanceof Email) {
                // prepare the list of association targets
                $targets = [];
                $this->addSenderOwner($targets, $entity);
//                $this->addRecipientOwners($targets, $entity);
                // add associations
                $hasChanges = $this->activityManager->addActivityTargets($entity, $targets);
                // recompute change set if needed
                if ($hasChanges) {
                    $uow->computeChangeSet(
                        $em->getClassMetadata(ClassUtils::getClass($entity)),
                        $entity
                    );
                }
            }
        }
    }

    /**
     * @param Email  $email
     * @param object $target
     *
     * @return bool TRUE if the association was added; otherwise, FALSE
     */
    public function addAssociation(Email $email, $target)
    {
        return $this->activityManager->addActivityTarget($email, $target);
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addSenderOwner(&$targets, Email $email)
    {
        $from = $email->getFromEmailAddress();
        if ($from) {
            $owner = $from->getOwner();
            if ($owner) {
                $this->addTarget($targets, $owner);
            }
        }
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addRecipientOwners(&$targets, Email $email)
    {
        $recipients = $email->getRecipients();
        foreach ($recipients as $recipient) {
            $owner = $recipient->getEmailAddress()->getOwner();
            if ($owner) {
                $this->addTarget($targets, $owner);
            }
        }
    }

    /**
     * @param object[] $targets
     * @param object   $target
     */
    protected function addTarget(&$targets, $target)
    {
        $alreadyExists = false;
        foreach ($targets as $existingTarget) {
            if ($target === $existingTarget) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $targets[] = $target;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeActivityTarget(ActivityInterface $activityEntity, $targetEntity)
    {
        return $this->activityManager->removeActivityTarget($activityEntity, $targetEntity);
    }
}
