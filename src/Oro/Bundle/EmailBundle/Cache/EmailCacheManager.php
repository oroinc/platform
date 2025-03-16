<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a way to check that an email body is cached.
 */
class EmailCacheManager
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private EmailBodySynchronizer $emailBodySynchronizer
    ) {
    }

    /**
     * Check that an email body is cached.
     * If do not, load it using appropriate email extension add it to a cache.
     */
    public function ensureEmailBodyCached(Email $email): void
    {
        if ($email->getEmailBody() === null) {
            // Additional load attempt, which is performed only on UI email expanding.
            // Console command oro:cron:email-body-sync marks email as synced even body was not loaded.
            $this->emailBodySynchronizer->syncOneEmailBody($email, true);
        }

        $this->eventDispatcher->dispatch(new EmailBodyLoaded($email), EmailBodyLoaded::NAME);
    }
}
