<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;

class ReminderManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var ReminderManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new ReminderManager($this->entityManager);
    }

    public function testSaveRemindersOnCreate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $reminders = new ArrayCollection(array($fooReminder,$barReminder));

        $reminderData = $this->createReminderData();

        $entityId = 101;
        $entity = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\RemindableInterface');
        $entityClass = get_class($entity);

        $this->expectGetEntityIdentifier($entity, $entityId);

        $entity->expects($this->once(0))
            ->method('getReminders')
            ->will($this->returnValue($reminders));

        $entity->expects($this->once())
            ->method('getReminderData')
            ->will($this->returnValue($reminderData));

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);
        $this->expectReminderSync($barReminder, $entityClass, $entityId, $reminderData);

        $this->entityManager->expects($this->at(1))
            ->method('persist')
            ->with($fooReminder);

        $this->entityManager->expects($this->at(2))
            ->method('persist')
            ->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    protected function expectReminderSync(
        \PHPUnit_Framework_MockObject_MockObject $reminder,
        $entityClassName,
        $entityId,
        ReminderDataInterface $reminderData
    ) {
        $reminder->expects($this->once())
            ->method('setRelatedEntityClassName')
            ->with($entityClassName);

        $reminder->expects($this->once())
            ->method('setRelatedEntityId')
            ->with($entityId);

        $reminder->expects($this->once())
            ->method('setReminderData')
            ->with($reminderData);
    }

    protected function expectGetEntityIdentifier($entity, $identifier)
    {
        $metadata = $this->getMockBuilder('Doctrine\\ORM\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->will($this->returnValue($metadata));

        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->will($this->returnValue(array('id' => $identifier)));
    }

    public function testSaveRemindersOnUpdate()
    {

    }

    protected function createReminder($id)
    {
        $result = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
        $result->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        return $result;
    }

    protected function createReminderData()
    {
        return $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\ReminderDataInterface');
    }
}
