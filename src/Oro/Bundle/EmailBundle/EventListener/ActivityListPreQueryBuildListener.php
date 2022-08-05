<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

/**
 * Adds email thread ids to the activity list target ids for threaded emails.
 */
class ActivityListPreQueryBuildListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function prepareIdsForEmailThreadEvent(ActivityListPreQueryBuildEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($event->getTargetClass() === Email::class) {
            /** @var Email $email */
            $email = $this->doctrineHelper->getEntity(Email::class, $event->getTargetId());
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
