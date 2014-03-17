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
        $this->manager       = new ReminderManager($this->entityManager);
    }

    public function testSaveRemindersOnCreate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $reminders   = new ArrayCollection(array($fooReminder, $barReminder));

        $reminderData = $this->createReminderData();

        $entityId    = 101;
        $entity      = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\RemindableInterface');
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

    public function testSaveRemindersOnUpdate()
    {
        $fooReminder         = $this->createReminder(100);
        $barReminder         = $this->createReminder(200);
        $remindersCollection =
            $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Entity\\Collection\\RemindersPersistentCollection')
                ->disableOriginalConstructor()
                ->getMock();

        $remindersCollection->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($fooReminder))));

        $remindersCollection->expects($this->once())
            ->method('isDirty')
            ->will($this->returnValue(true));

        $remindersCollection->expects($this->once())
            ->method('getInsertDiff')
            ->will($this->returnValue(array($fooReminder)));

        $remindersCollection->expects($this->once())
            ->method('getDeleteDiff')
            ->will($this->returnValue(array($barReminder)));

        $reminderData = $this->createReminderData();

        $entityId    = 101;
        $entity      = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\RemindableInterface');
        $entityClass = get_class($entity);

        $this->expectGetEntityIdentifier($entity, $entityId);

        $entity->expects($this->once(0))
            ->method('getReminders')
            ->will($this->returnValue($remindersCollection));

        $entity->expects($this->once())
            ->method('getReminderData')
            ->will($this->returnValue($reminderData));

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);

        $this->entityManager->expects($this->at(1))
            ->method('persist')
            ->with($fooReminder);

        $this->entityManager->expects($this->at(2))
            ->method('remove')
            ->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    public function testLoadReminders()
    {
        $entityId    = 101;
        $entity      = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\RemindableInterface');
        $entityClass = get_class($entity);
        $this->expectGetEntityIdentifier($entity, $entityId);

        $repository = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Entity\\Repository\\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->will($this->returnValue($repository));

        $entity->expects($this->once())
            ->method('setReminders')
            ->with(
                $this->callback(
                    function ($reminders) use ($repository, $entityId, $entityClass) {
                        $this->assertInstanceOf(
                            'Oro\\Bundle\\ReminderBundle\\Entity\\Collection\\RemindersPersistentCollection',
                            $reminders
                        );
                        $this->assertAttributeEquals($entityClass, 'className', $reminders);
                        $this->assertAttributeEquals($entityId, 'identifier', $reminders);
                        return true;
                    }
                )
            );

        $this->manager->loadReminders($entity);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Entity "Reminder_Mock" with multiple identifiers "foo", "bar" is not supported by OroReminderBundle
     */
    // @codingStandardsIgnoreEnd
    public function testInvalidEntityIdentifier()
    {
        $entity = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Entity\\RemindableInterface')
            ->disableOriginalConstructor()
            ->setMockClassName('Reminder_Mock')
            ->getMock();

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
            ->will($this->returnValue(array('foo' => 100, 'bar' => 200)));

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
