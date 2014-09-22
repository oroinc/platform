<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;

class TestEmailSynchronizer extends AbstractEmailSynchronizer
{
    const EMAIL_ORIGIN_ENTITY = 'AcmeBundle:EmailOrigin';

    private $now;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        KnownEmailAddressChecker $knownEmailAddressChecker
    ) {
        parent::__construct($doctrine, $emailEntityBuilder, $emailAddressManager, $emailAddressHelper);
        $this->knownEmailAddressChecker = $knownEmailAddressChecker;
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
            $this->log,
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            $this->knownEmailAddressChecker
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

    public function callDoSyncOrigin(EmailOrigin $origin)
    {
        $this->doSyncOrigin($origin);
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
