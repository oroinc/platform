<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationBag;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;

class TestEmailSynchronizer extends AbstractEmailSynchronizer
{
    public const EMAIL_ORIGIN_ENTITY = 'AcmeBundle:EmailOrigin';

    /** @var EmailEntityBuilder */
    private $emailEntityBuilder;

    /** @var \DateTime|null */
    private $now;

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

    public function supports(EmailOrigin $origin)
    {
        return true;
    }

    protected function getEmailOriginClass()
    {
        return self::EMAIL_ORIGIN_ENTITY;
    }

    protected function createSynchronizationProcessor($origin)
    {
        return new TestEmailSynchronizationProcessor(
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $this->getKnownEmailAddressChecker()
        );
    }

    protected function getCurrentUtcDateTime()
    {
        return $this->now;
    }

    public function setCurrentUtcDateTime(\DateTime $now)
    {
        $this->now = $now;
    }

    public function callDoSyncOrigin(EmailOrigin $origin, SynchronizationProcessorSettings $settings = null)
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

    public function callResetHangedOrigins()
    {
        $this->resetHangedOrigins();
    }
}
