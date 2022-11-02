<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailAddressProxy;

class EmailAddressManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EmailAddressManager */
    private $emailAddressManager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->emailAddressManager = new EmailAddressManager(
            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures',
            'Test%sProxy',
            $this->doctrine
        );
    }

    public function testNewEmailAddress(): void
    {
        $this->assertInstanceOf(TestEmailAddressProxy::class, $this->emailAddressManager->newEmailAddress());
    }

    public function testGetEntityManager(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEmailAddressProxy::class)
            ->willReturn($em);

        $this->assertSame($em, $this->emailAddressManager->getEntityManager());
    }

    public function testGetEmailAddressRepositoryWhenEntityManagerProvided(): void
    {
        $repo = $this->createMock(EntityRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(TestEmailAddressProxy::class)
            ->willReturn($repo);

        $this->assertSame($repo, $this->emailAddressManager->getEmailAddressRepository($em));
    }

    public function testGetEmailAddressRepositoryWhenEntityManagerDoesNotProvided(): void
    {
        $repo = $this->createMock(EntityRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(TestEmailAddressProxy::class)
            ->willReturn($repo);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEmailAddressProxy::class)
            ->willReturn($em);

        $this->assertSame($repo, $this->emailAddressManager->getEmailAddressRepository());
    }

    public function testGetEmailAddressProxyClass(): void
    {
        $this->assertEquals(TestEmailAddressProxy::class, $this->emailAddressManager->getEmailAddressProxyClass());
    }
}
