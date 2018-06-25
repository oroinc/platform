<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;

class NotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_EVENT_NAME = 'namespace.event_name';

    /**
     * @var NotificationManager
     */
    protected $manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entity;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var EventHandlerInterface
     */
    protected $handler;

    /**
     * @var ArrayCollection
     */
    protected $rules;

    protected function setUp()
    {
        $this->em = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->className = 'Oro\Bundle\NotificationBundle\Entity\EmailNotification';
        $this->handler = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity = $this->createMock(\stdClass::class);
        $this->rules = new ArrayCollection(array());

        $repository = $this->getMockBuilder(
            'Oro\Bundle\NotificationBundle\Entity\Repository\EmailNotificationRepository'
        )->disableOriginalConstructor()->getMock();

        $repository->expects($this->once())->method('getRules')
            ->will($this->returnValue($this->rules));

        $this->em->expects($this->once())->method('getRepository')
            ->with($this->equalTo($this->className))
            ->will($this->returnValue($repository));

        $this->manager = new NotificationManager($this->em, $this->className);
        $this->manager->addHandler($this->handler);
    }

    protected function tearDown()
    {
        unset($this->em);
        unset($this->className);
        unset($this->handler);
        unset($this->entity);
        unset($this->rules);
        unset($this->manager);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProcess($eventPropagationStopped)
    {
        $notificationEventMock = $this->createMock(
            'Oro\Bundle\NotificationBundle\Event\NotificationEvent',
            array(),
            array($this->entity)
        );
        $notificationEventMock->expects($this->once())->method('getEntity')
            ->will($this->returnValue($this->entity));
        $notificationEventMock->expects($this->once())->method('isPropagationStopped')
            ->will($this->returnValue($eventPropagationStopped));

        $event = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Event')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->at(0))->method('getName')
            ->will($this->returnValue(self::TEST_EVENT_NAME));
        $event->expects($this->at(1))->method('getName')
            ->will($this->returnValue(self::TEST_EVENT_NAME . ' not the same'));

        $this->handler->expects($this->once())->method('handle');

        $rule = $this->createMock($this->className);
        $rule->expects($this->exactly(2))->method('getEntityName')
            ->will($this->returnValue(get_class($this->entity)));
        $rule->expects($this->exactly(2))->method('getEvent')
            ->will($this->returnValue($event));

        $this->rules->add($rule);
        $this->rules->add($rule);

        $this->manager->process($notificationEventMock, self::TEST_EVENT_NAME);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }

    /**
     * Test setters, getters
     */
    public function testAddAndGetHandlers()
    {
        $this->assertCount(1, $this->manager->getHandlers());

        $handler = $this->createMock('Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface');
        $this->manager->addHandler($handler);

        $this->assertCount(2, $this->manager->getHandlers());
        $this->assertContains($handler, $this->manager->getHandlers());
    }
}
