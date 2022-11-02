<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class KnownEmailAddressCheckerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $em = $this->createMock(EntityManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $emailAddressManager = $this->createMock(EmailAddressManager::class);
        $emailAddressHelper = $this->createMock(EmailAddressHelper::class);
        $emailOwnerProviderStorage = $this->createMock(EmailOwnerProviderStorage::class);

        $doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);
        $doctrine->expects($this->once())
            ->method('resetManager');

        $factory = new KnownEmailAddressCheckerFactory(
            $doctrine,
            $emailAddressManager,
            $emailAddressHelper,
            $emailOwnerProviderStorage,
            []
        );

        $result = $factory->create();
        $this->assertInstanceOf(KnownEmailAddressChecker::class, $result);
    }
}
