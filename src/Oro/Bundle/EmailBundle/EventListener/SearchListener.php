<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Adds "organization" field to the search entity map of Email entity.
 */
class SearchListener
{
    private const EMPTY_ORGANIZATION_ID = 0;

    public function prepareEntityMapEvent(PrepareEntityMapEvent $event): void
    {
        $className = $event->getClassName();
        if (Email::class === $className) {
            $organizationsId = [];
            /** @var Email $entity */
            $entity = $event->getEntity();
            $recipients = $entity->getRecipients();
            foreach ($recipients as $recipient) {
                $owner = $recipient->getEmailAddress()->getOwner();
                if ($owner instanceof UserInterface && $owner instanceof OrganizationAwareInterface) {
                    $organizationsId[] = $owner->getOrganization()->getId();
                }
            }

            $data = $event->getData();
            $data['integer']['organization'] = empty($organizationsId)
                ? self::EMPTY_ORGANIZATION_ID
                : array_unique($organizationsId);
            $event->setData($data);
        }
    }
}
