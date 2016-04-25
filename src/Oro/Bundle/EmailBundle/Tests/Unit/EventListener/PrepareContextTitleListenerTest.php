<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Symfony\Component\Routing\Router;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\EmailBundle\EventListener\PrepareContextTitleListener;

class PrepareContextTitleListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PrepareContextTitleListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Router */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PrepareContextTitleListener($this->router, $this->doctrineHelper);
    }

    public function testPrepareContextTitleEventSkippEntity()
    {
        $item = [];
        $item['title'] = 'title';
        $targetClass = 'test';
        $expectedItem = ['title' => 'title'];

        $event = new PrepareContextTitleEvent($item, $targetClass);
        $this->listener->prepareEmailContextTitleEvent($event);

        $this->assertEquals($expectedItem, $event->getItem());
    }

    public function testPrepareContextTitleDataEvent()
    {
        $item = [];
        $item['title'] = 'title';
        $item['targetId'] = 1;
        $targetClass = Email::ENTITY_CLASS;
        $expectedItem = ['title' => 'new title', 'link' => 'link', 'targetId' => 1];

        $entity = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturn('link');

        $entity->expects($this->once())
            ->method('getSubject')
            ->willReturn('new title');

        $event = new PrepareContextTitleEvent($item, $targetClass);
        $this->listener->prepareEmailContextTitleEvent($event);

        $this->assertEquals($expectedItem, $event->getItem());
    }
}
