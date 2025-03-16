<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use PHPUnit\Framework\TestCase;

class KnownEmailAddressCheckerFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $emailAddressManager = $this->createMock(EmailAddressManager::class);
        $emailAddressHelper = $this->createMock(EmailAddressHelper::class);
        $emailOwnerProviderStorage = $this->createMock(EmailOwnerProviderStorage::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $doctrine->expects(self::exactly(2))
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(false);
        $doctrine->expects(self::once())
            ->method('resetManager');

        $factory = new KnownEmailAddressCheckerFactory(
            $doctrine,
            $emailAddressManager,
            $emailAddressHelper,
            $emailOwnerProviderStorage,
            []
        );

        $result = $factory->create();
        self::assertInstanceOf(KnownEmailAddressChecker::class, $result);
    }
}
