<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;

class SearchListener
{
    const EMAIL_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Email';
    const EMPTY_ORGANIZATION_ID = 0;

    /**
     * @param PrepareEntityMapEvent $event
     */
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $data      = $event->getData();
        $className = $event->getClassName();
        if ($className === self::EMAIL_CLASS_NAME) {
            /** @var $entity Email */
            $entity          = $event->getEntity();
            $organizationsId = [];
            $recipients      = $entity->getRecipients();
            /** @var  $recipient EmailRecipient */
            foreach ($recipients as $recipient) {
                $owner = $recipient->getEmailAddress()->getOwner();
                if ($owner instanceof UserInterface && $owner instanceof OrganizationAwareInterface) {
                    $organizationsId[] = $owner->getOrganization()->getId();
                }
            }
            if (!isset($data['integer'])) {
                $data['integer'] = [];
            }
            if (!empty($organizationsId)) {
                $data['integer']['organization'] = array_unique($organizationsId);
            } else {
                $data['integer']['organization'] = self::EMPTY_ORGANIZATION_ID;
            }
        }

        $event->setData($data);
    }
}
