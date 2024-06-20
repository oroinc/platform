<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Event\EmailUserAdded;

/**
 * Updates the visibility for new email user.
 */
class UpdateVisibilityForNewEmailUserListener
{
    public function __construct(
        private EmailAddressVisibilityManager $emailAddressVisibilityManager
    ) {
    }

    public function onEmailUserAdded(EmailUserAdded $event): void
    {
        $this->emailAddressVisibilityManager->processEmailUserVisibility($event->getEmailUser());
    }
}
