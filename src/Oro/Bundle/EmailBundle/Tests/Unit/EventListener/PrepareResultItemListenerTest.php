<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\EventListener\PrepareResultItemListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\Routing\Router;

class PrepareResultItemListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PrepareResultItemListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Router */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Item */
    protected $item;

    public function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PrepareResultItemListener($this->router, $this->doctrineHelper);
    }

    public function testPrepareEmailItemDataEventSkippEntity()
    {
        $this->item->expects($this->once())
            ->method('getEntityName')
            ->willReturn('test');

        $event = new PrepareResultItemEvent($this->item);
        $this->listener->prepareEmailItemDataEvent($event);
    }

    public function testPrepareEmailItemDataEvent()
    {
        $entity = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->getMock();

        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->item->expects($this->exactly(1))
            ->method('getId');

        $this->item->expects($this->exactly(1))
            ->method('getEntityName')
            ->willReturn(EmailUser::ENTITY_CLASS);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(EmailUser::ENTITY_CLASS)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('find')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $email->expects($this->once())
            ->method('getId');

        $event = new PrepareResultItemEvent($this->item);
        $this->listener->prepareEmailItemDataEvent($event);
    }
}
