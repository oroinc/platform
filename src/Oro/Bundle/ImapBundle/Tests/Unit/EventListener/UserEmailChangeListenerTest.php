<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\UserEmailChangeListener;
use Oro\Bundle\UserBundle\Entity\User;

class UserEmailChangeListenerTest extends \PHPUnit_Framework_TestCase
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
        $user = new User();
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('email')
            ->willReturn(false);

        $args->expects($this->never())
            ->method('getEntityManager');

        $this->listener->preUpdate($user, $args);
    }

    public function testPreUpdateWithoutImapConfiguration()
    {
        $user = new User();
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('email')
            ->willReturn(true);

        $args->expects($this->never())
            ->method('getEntityManager');

        $this->listener->preUpdate($user, $args);
    }

    public function testPreUpdateWithImapConfiguration()
    {
        $user = new User();
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setUser('user');
        $user->setImapConfiguration($userEmailOrigin);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('email')
            ->willReturn(true);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('flush')
            ->with($userEmailOrigin);

        $args->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->listener->preUpdate($user, $args);

        $this->assertNull($user->getImapConfiguration());
    }
}
