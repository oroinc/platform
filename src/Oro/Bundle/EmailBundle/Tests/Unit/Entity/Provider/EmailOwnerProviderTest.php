<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailOwnerProviderTest extends \PHPUnit\Framework\TestCase
{
    private function getEmailOwnerProviderStorageMock(array $providers)
    {
        $storage = $this->createMock(EmailOwnerProviderStorage::class);
        $storage->expects($this->any())
            ->method('getProviders')
            ->willReturn($providers);

        return $storage;
    }

    public function testFindEmailOwnerFirstProvider()
    {
        $em = $this->createMock(EntityManager::class);
        $result = $this->createMock(EmailOwnerInterface::class);
        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects($this->once())
            ->method('findEmailOwner')
            ->with($this->identicalTo($em), 'test')
            ->willReturn($result);
        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects($this->never())
            ->method('findEmailOwner');

        $provider = new EmailOwnerProvider($this->getEmailOwnerProviderStorageMock([$provider1, $provider2]));
        $this->assertEquals($result, $provider->findEmailOwner($em, 'test'));
    }

    public function testFindEmailOwnerSecondProvider()
    {
        $em = $this->createMock(EntityManager::class);
        $result = $this->createMock(EmailOwnerInterface::class);
        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects($this->once())
            ->method('findEmailOwner')
            ->with($this->identicalTo($em), 'test')
            ->willReturn(null);
        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects($this->once())
            ->method('findEmailOwner')
            ->with($this->identicalTo($em), 'test')
            ->willReturn($result);

        $provider = new EmailOwnerProvider($this->getEmailOwnerProviderStorageMock([$provider1, $provider2]));
        $this->assertEquals($result, $provider->findEmailOwner($em, 'test'));
    }

    public function testFindEmailOwnerNotFound()
    {
        $em = $this->createMock(EntityManager::class);
        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects($this->once())
            ->method('findEmailOwner')
            ->with($this->identicalTo($em), 'test')
            ->willReturn(null);
        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects($this->once())
            ->method('findEmailOwner')
            ->with($this->identicalTo($em), 'test')
            ->willReturn(null);

        $provider = new EmailOwnerProvider($this->getEmailOwnerProviderStorageMock([$provider1, $provider2]));
        $this->assertNull($provider->findEmailOwner($em, 'test'));
    }
}
