<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailOwnerProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailOwnerProviderStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailOwnerProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(EmailOwnerProviderStorage::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->provider = new EmailOwnerProvider($this->storage);
    }

    public function testFindEmailOwnerFirstProvider(): void
    {
        $email = 'test@example.com';
        $foundOwner = $this->createMock(EmailOwnerInterface::class);

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn($foundOwner);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::never())
            ->method('findEmailOwner');

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2]);

        self::assertSame($foundOwner, $this->provider->findEmailOwner($this->em, $email));
    }

    public function testFindEmailOwnerSecondProvider(): void
    {
        $email = 'test@example.com';
        $foundOwner = $this->createMock(EmailOwnerInterface::class);

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn(null);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn($foundOwner);

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2]);

        self::assertSame($foundOwner, $this->provider->findEmailOwner($this->em, $email));
    }

    public function testFindEmailOwnerNotFound(): void
    {
        $email = 'test@example.com';

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn(null);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn(null);

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2]);

        self::assertNull($this->provider->findEmailOwner($this->em, $email));
    }

    public function testFindEmailOwners(): void
    {
        $email = 'test@example.com';
        $foundOwner = $this->createMock(EmailOwnerInterface::class);

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn(null);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::once())
            ->method('findEmailOwner')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn($foundOwner);

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2]);

        self::assertSame([$foundOwner], $this->provider->findEmailOwners($this->em, $email));
    }

    public function testGetOrganizations(): void
    {
        $email = 'test@example.com';

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::never())
            ->method('getEmailOwnerClass');
        $provider1->expects(self::once())
            ->method('getOrganizations')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn([]);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::once())
            ->method('getEmailOwnerClass')
            ->willReturn('Test\Owner2');
        $provider2->expects(self::once())
            ->method('getOrganizations')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn([1, 2]);

        $provider3 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider3->expects(self::once())
            ->method('getEmailOwnerClass')
            ->willReturn('Test\Owner3');
        $provider3->expects(self::once())
            ->method('getOrganizations')
            ->with(self::identicalTo($this->em), $email)
            ->willReturn([1]);

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2, $provider3]);

        self::assertSame(
            ['Test\Owner2' => [1, 2], 'Test\Owner3' => [1]],
            $this->provider->getOrganizations($this->em, $email)
        );
    }

    public function testGetEmails(): void
    {
        $organizationId = 123;

        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider1->expects(self::once())
            ->method('getEmailOwnerClass')
            ->willReturn('Test\Owner1');
        $provider1->expects(self::once())
            ->method('getEmails')
            ->with(self::identicalTo($this->em), $organizationId)
            ->willReturn([]);

        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2->expects(self::once())
            ->method('getEmailOwnerClass')
            ->willReturn('Test\Owner2');
        $provider2->expects(self::once())
            ->method('getEmails')
            ->with(self::identicalTo($this->em), $organizationId)
            ->willReturn(['test1@example.com', 'test2@example.com']);

        $provider3 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider3->expects(self::once())
            ->method('getEmailOwnerClass')
            ->willReturn('Test\Owner3');
        $provider3->expects(self::once())
            ->method('getEmails')
            ->with(self::identicalTo($this->em), $organizationId)
            ->willReturn(['test3@example.com']);

        $this->storage->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider1, $provider2, $provider3]);

        self::assertSame(
            [
                ['test1@example.com', 'Test\Owner2'],
                ['test2@example.com', 'Test\Owner2'],
                ['test3@example.com', 'Test\Owner3']
            ],
            iterator_to_array($this->provider->getEmails($this->em, $organizationId))
        );
    }
}
