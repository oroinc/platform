<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EmailActivityManager
{
    /** @var  ConfigProvider */
    protected $activityConfigProvider;

    /**
     * @param ConfigProvider $activityConfigProvider
     */
    public function __construct(ConfigProvider $activityConfigProvider)
    {
        $this->activityConfigProvider = $activityConfigProvider;
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
                $this->addRecipientOwners($targets, $entity);
                // add associations
                $hasChanges = $this->setAssociations($entity, $targets);
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
     * @return bool TRUE if the association was added; otherwise, FALSE
     */
    public function addAssociation(Email $email, $target)
    {
        return $this->setAssociations($email, [$target]);
    }

    /**
     * @param Email $email
     * @param array $targets
     * @return bool TRUE if at least one association was added; otherwise, FALSE
     */
    protected function setAssociations(Email $email, $targets)
    {
        $hasChanges = false;
        $emailClass = ClassUtils::getClass($email);
        foreach ($targets as $target) {
            $targetClass = ClassUtils::getClass($target);
            if (!$this->activityConfigProvider->hasConfig($targetClass)) {
                continue;
            }
            $config     = $this->activityConfigProvider->getConfig($targetClass);
            $activities = $config->get('activities');
            if (empty($activities) || !in_array($emailClass, $activities)) {
                continue;
            }
            $email->addActivityTarget($target);
            $hasChanges = true;
        }

        return $hasChanges;
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
}
