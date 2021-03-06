<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailCacheManager
{
    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var EmailBodySynchronizer */
    protected $emailBodySynchronizer;

    public function __construct(
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher,
        EmailBodySynchronizer $emailBodySynchronizer
    ) {
        $this->em                    = $em;
        $this->eventDispatcher       = $eventDispatcher;
        $this->emailBodySynchronizer = $emailBodySynchronizer;
    }

    /**
     * Check that email body is cached.
     * If do not, load it using appropriate email extension add it to a cache.
     */
    public function ensureEmailBodyCached(Email $email)
    {
        if ($email->getEmailBody() === null) {
            // Additional load attempt, which is performed only on UI email expanding.
            // Console command oro:cron:email-body-sync marks email as synced even body was not loaded.
            $this->emailBodySynchronizer->syncOneEmailBody($email, true);
        }

        $this->eventDispatcher->dispatch(new EmailBodyLoaded($email), EmailBodyLoaded::NAME);
    }
}
