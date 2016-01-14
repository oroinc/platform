<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Symfony\Component\Routing\Router;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\EmailBundle\EventListener\PrepareResultItemListener;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

class PrepareResultItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PrepareResultItemListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Router */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Item */
    protected $item;

    public function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PrepareResultItemListener($this->router);
    }

    public function testPrepareEmailItemDataEventSkippEntity()
    {
        $this->item->expects($this->once())
            ->method('getEntityName')
            ->willReturn('test');
        $this->item->expects($this->never())
            ->method('getEntity');

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

        $this->item->expects($this->once())
            ->method('getEntityName')
            ->willReturn(EmailUser::ENTITY_CLASS);
        $this->item->expects($this->once())
            ->method('getEntity')
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
