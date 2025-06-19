<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationBag;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;

class TestEmailSynchronizer extends AbstractEmailSynchronizer
{
    public const EMAIL_ORIGIN_ENTITY = 'AcmeBundle:EmailOrigin';

    private EmailEntityBuilder $emailEntityBuilder;
    private ?\DateTime $now = null;

    public function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        EmailEntityBuilder $emailEntityBuilder,
        NotificationAlertManager $notificationAlertManager
    ) {
        parent::__construct($doctrine, $knownEmailAddressCheckerFactory, $notificationAlertManager);
        $this->notificationsBag = new EmailSyncNotificationBag();
        $this->emailEntityBuilder = $emailEntityBuilder;
    }

    #[\Override]
    public function supports(EmailOrigin $origin): bool
    {
        return true;
    }

    #[\Override]
    protected function getEmailOriginClass(): string
    {
        return self::EMAIL_ORIGIN_ENTITY;
    }

    #[\Override]
    protected function createSynchronizationProcessor($origin): AbstractEmailSynchronizationProcessor
    {
        return new TestEmailSynchronizationProcessor(
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $this->getKnownEmailAddressChecker()
        );
    }

    #[\Override]
    protected function getCurrentUtcDateTime(): \DateTime
    {
        return $this->now;
    }

    public function setCurrentUtcDateTime(\DateTime $now)
    {
        $this->now = $now;
    }

    public function callDoSyncOrigin(EmailOrigin $origin, ?SynchronizationProcessorSettings $settings = null)
    {
        $this->doSyncOrigin($origin, $settings);
    }

    public function callChangeOriginSyncState(EmailOrigin $origin, $syncCode, $synchronizedAt)
    {
        return $this->changeOriginSyncState($origin, $syncCode, $synchronizedAt);
    }

    public function callFindOriginToSync($maxConcurrentTasks, $minExecPeriodInMin)
    {
        return $this->findOriginToSync($maxConcurrentTasks, $minExecPeriodInMin);
    }

    public function callFindOrigin($originId)
    {
        return $this->findOrigin($originId);
    }

    public function callResetHangedOrigins()
    {
        $this->resetHangedOrigins();
    }
}
