<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\EventListener\ReminderListener;
use Oro\Bundle\ReminderBundle\Tests\Unit\Fixtures\RemindableEntity;

class ReminderListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReminderListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ReminderManager
     */
    protected $reminderManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->reminderManager = $this
            ->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ReminderListener($this->reminderManager);
    }

    /**
     * @param object $entity
     * @param bool $expected
     *
     * @dataProvider entityDataProvider
     */
    public function testPostLoad($entity, $expected)
    {
        $event = new LifecycleEventArgs($entity, $this->entityManager);

        if ($expected) {
            $this->reminderManager
                ->expects($this->once())
                ->method('loadReminders')
                ->with($entity);
        }

        $this->listener->postLoad($event);
    }


    /**
     * @param object $entity
     * @param bool $expected
     *
     * @dataProvider entityDataProvider
     */
    public function testPostPersist($entity, $expected)
    {
        $event = new LifecycleEventArgs($entity, $this->entityManager);

        if ($expected) {
            $this->reminderManager
                ->expects($this->once())
                ->method('saveReminders')
                ->with($entity);
        }

        $this->listener->postPersist($event);
    }

    /**
     * @return array
     */
    public function entityDataProvider()
    {
        $event = new RemindableEntity();
        $event->setReminders(new ArrayCollection([new Reminder()]));

        return [
            [null, false],
            [new \stdClass(), false],
            [new RemindableEntity(), false],
            [$event, true],
        ];
    }
}
