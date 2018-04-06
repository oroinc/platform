<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;

class TestEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker
    ) {
        parent::__construct($em, $emailEntityBuilder, $knownEmailAddressChecker);
    }

    public function process(EmailOrigin $origin, $syncStartTime)
    {
    }
}
