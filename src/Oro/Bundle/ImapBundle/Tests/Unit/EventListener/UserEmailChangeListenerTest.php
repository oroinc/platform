<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\UserEmailChangeListener;
use Oro\Bundle\UserBundle\Entity\User;

class UserEmailChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserEmailChangeListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new UserEmailChangeListener();
    }

    public function testPreUpdateEmailNotChanged()
    {
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setUser('user');

        $user = new User();
        $user->setImapConfiguration($userEmailOrigin);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('email')
            ->willReturn(false);

        $args->expects($this->never())
            ->method('getEntityManager');

        $this->listener->preUpdate($user, $args);

        $this->assertEquals('user', $user->getImapConfiguration()->getUser());
    }

    public function testPreUpdateWithImapConfiguration()
    {
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setUser('user');

        $user = new User();
        $user->setImapConfiguration($userEmailOrigin);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('email')
            ->willReturn(true);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($classMetadata, $userEmailOrigin);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(UserEmailOrigin::class)
            ->willReturn($classMetadata);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $args->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->listener->preUpdate($user, $args);

        $this->assertNull($user->getImapConfiguration());
    }
}
