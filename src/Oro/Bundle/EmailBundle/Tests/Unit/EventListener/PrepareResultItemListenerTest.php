<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\EventListener\PrepareResultItemListener;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PrepareResultItemListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PrepareResultItemListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new PrepareResultItemListener($this->urlGenerator, $this->doctrine);
    }

    public function testPrepareResultItemForNotEmailUserEntity()
    {
        $event = new PrepareResultItemEvent(new Item('test'));
        $this->listener->prepareResultItem($event);
        $this->assertEquals('test', $event->getResultItem()->getEntityName());
    }

    public function testPrepareResultItem()
    {
        $emailId = 100;
        $emailUrl = 'test-email-url';

        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getId')
            ->willReturn($emailId);
        $entity = $this->createMock(EmailUser::class);
        $entity->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $item = new Item(EmailUser::class, 123);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(EmailUser::class, $item->getId())
            ->willReturn($entity);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($em);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_email_thread_view', ['id' => $emailId], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($emailUrl);

        $event = new PrepareResultItemEvent($item);
        $this->listener->prepareResultItem($event);

        $this->assertEquals(Email::class, $event->getResultItem()->getEntityName());
        $this->assertEquals($emailId, $event->getResultItem()->getRecordId());
        $this->assertEquals($emailUrl, $event->getResultItem()->getRecordUrl());
    }
}
