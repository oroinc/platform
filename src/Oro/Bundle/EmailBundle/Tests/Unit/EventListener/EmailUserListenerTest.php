<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\EventListener\EmailUserListener;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailUserListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(WebSocketSendProcessor::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new EmailUserListener($this->processor);
    }

    public function testFlush()
    {
        $changeSetAnswer = ['seen' => true];

        $user1 = new User();
        $user1->setId(1);
        $user2 = new User();
        $user2->setId(2);
        $emailUser1 = new EmailUser();
        $emailUser1->setOwner($user1);
        $emailUser2 = new EmailUser();
        $emailUser2->setOwner($user2);

        $emailUserArray = [$emailUser1, $emailUser2, $emailUser1];

        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $onFlushEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->uow->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn($changeSetAnswer);
        $this->uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($emailUserArray);
        $this->uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($emailUserArray);
        $this->processor
            ->expects($this->once())
            ->method('send')
            ->with(
                [
                    $user1->getId() => ['entity' => $emailUser1, 'new' => 2],
                    $user2->getId() => ['entity' => $emailUser2, 'new' => 1]
                ]
            );

        $postFlushEventArgs = $this->createMock(PostFlushEventArgs::class);
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
