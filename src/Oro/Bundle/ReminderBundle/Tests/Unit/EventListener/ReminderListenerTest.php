<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\EventListener\ReminderListener;
use Oro\Bundle\ReminderBundle\Tests\Unit\Fixtures\RemindableEntity;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ReminderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ReminderManager */
    private $reminderManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $entityManager;

    /** @var ReminderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->reminderManager = $this->createMock(ReminderManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_reminder.entity.manager', $this->reminderManager)
            ->getContainer($this);

        $this->listener = new ReminderListener($container);
    }

    /**
     * @dataProvider entityDataProvider
     */
    public function testPostLoad(?object $entity, bool $expected)
    {
        $event = new LifecycleEventArgs($entity, $this->entityManager);

        if ($expected) {
            $this->reminderManager->expects($this->once())
                ->method('loadReminders')
                ->with($entity);
        }

        $this->listener->postLoad($event);
    }

    /**
     * @dataProvider entityDataProvider
     */
    public function testPostPersist(?object $entity, bool $expected)
    {
        $event = new LifecycleEventArgs($entity, $this->entityManager);

        if ($expected) {
            $this->reminderManager->expects($this->once())
                ->method('saveReminders')
                ->with($entity);
        }

        $this->listener->postPersist($event);
    }

    public function entityDataProvider(): array
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
