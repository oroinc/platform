<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class ActivityListPreQueryBuildListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Add email thread ids to qb params
     *
     * @param ActivityListPreQueryBuildEvent $event
     */
    public function prepareIdsForEmailThreadEvent(ActivityListPreQueryBuildEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($event->getTargetClass() === Email::ENTITY_CLASS) {
            /** @var Email $email */
            $email = $this->doctrineHelper->getEntity(Email::ENTITY_CLASS, $event->getTargetId());
            if ($email->getThread()) {
                $emailIds = array_map(
                    function ($emailEntity) {
                        return $emailEntity->getId();
                    },
                    $email->getThread()->getEmails()->toArray()
                );
                $event->setTargetIds($emailIds);
            }
        }
    }
}
