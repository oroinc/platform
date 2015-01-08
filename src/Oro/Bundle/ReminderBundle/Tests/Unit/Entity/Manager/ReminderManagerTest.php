<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Collection;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;

class ReminderManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ReminderManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->entityManager));

        $this->manager = new ReminderManager($this->doctrineHelper);
    }

    public function testSaveRemindersOnCreate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $reminders = new ArrayCollection([$fooReminder, $barReminder]);

        $reminderData = $this->createReminderData();

        $entityId = 101;
        /** @var \PHPUnit_Framework_MockObject_MockObject|RemindableInterface $entity */
        $entity = $this->getMock('Oro\Bundle\ReminderBundle\Entity\RemindableInterface');
        $entityClass = get_class($entity);

        $entity->expects($this->once())
            ->method('getReminders')
            ->will($this->returnValue($reminders));

        $entity->expects($this->once())
            ->method('getReminderData')
            ->will($this->returnValue($reminderData));

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);
        $this->expectReminderSync($barReminder, $entityClass, $entityId, $reminderData);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($entityId));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass));

        $this->entityManager->expects($this->at(0))
            ->method('persist')
            ->with($fooReminder);

        $this->entityManager->expects($this->at(1))
            ->method('persist')
            ->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    public function testSaveEmptyEntityId()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|RemindableInterface $entity */
        $entity = $this->getMock('Oro\Bundle\ReminderBundle\Entity\RemindableInterface');

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue(null));

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->manager->saveReminders($entity);
    }

    public function testSaveRemindersOnUpdate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $remindersCollection =
            $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection')
                ->disableOriginalConstructor()
                ->getMock();

        $remindersCollection->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$fooReminder])));

        $remindersCollection->expects($this->once())
            ->method('isDirty')
            ->will($this->returnValue(true));

        $remindersCollection->expects($this->once())
            ->method('getInsertDiff')
            ->will($this->returnValue([$fooReminder]));

        $remindersCollection->expects($this->once())
            ->method('getDeleteDiff')
            ->will($this->returnValue([$barReminder]));

        $reminderData = $this->createReminderData();

        $entityId = 101;
        /** @var \PHPUnit_Framework_MockObject_MockObject|RemindableInterface $entity */
        $entity = $this->getMock('Oro\Bundle\ReminderBundle\Entity\RemindableInterface');
        $entityClass = get_class($entity);

        $entity->expects($this->once())
            ->method('getReminders')
            ->will($this->returnValue($remindersCollection));

        $entity->expects($this->once())
            ->method('getReminderData')
            ->will($this->returnValue($reminderData));

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($entityId));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass));

        $this->entityManager
            ->expects($this->at(0))
            ->method('persist')
            ->with($fooReminder);

        $this->entityManager
            ->expects($this->at(1))
            ->method('remove')
            ->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    /**
     * @dataProvider emptyRemindersProvider
     */
    public function testSaveRemindersEmpty($reminders)
    {
        $entityId = 101;
        /** @var \PHPUnit_Framework_MockObject_MockObject|RemindableInterface $entity */
        $entity = $this->getMock('Oro\Bundle\ReminderBundle\Entity\RemindableInterface');
        $entityClass = get_class($entity);

        $entity->expects($this->once())
            ->method('getReminders')
            ->will($this->returnValue($reminders));

        $entity->expects($this->never())
            ->method('getReminderData');

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($entityId));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass));

        $this->manager->saveReminders($entity);
    }

    public function emptyRemindersProvider()
    {
        $remindersPersistentCollection =
            $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection')
                ->disableOriginalConstructor()
                ->getMock();
        $remindersPersistentCollection->expects($this->any())
            ->method('isEmpty')
            ->will($this->returnValue(true));

        return [
            [$remindersPersistentCollection],
            [new ArrayCollection()],
            [[]],
        ];
    }

    public function testLoadReminders()
    {
        $entityId = 101;
        /** @var \PHPUnit_Framework_MockObject_MockObject|RemindableInterface $entity */
        $entity = $this->getMock('Oro\Bundle\ReminderBundle\Entity\RemindableInterface');
        $entityClass = get_class($entity);

        $repository = $this->getReminderRepository();

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->will($this->returnValue($repository));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($entityId));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass));

        $entity->expects($this->once())
            ->method('setReminders')
            ->with(
                $this->callback(
                    function ($reminders) use ($repository, $entityId, $entityClass) {
                        $this->assertInstanceOf(
                            'Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection',
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

    public function testApplyRemindersNoItems()
    {
        $entityClassName = 'Oro\Bundle\ReminderBundle\Entity\Reminder';
        $items           = [];

        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $this->manager->applyReminders($items, $entityClassName);
    }

    public function testApplyRemindersNotRemindableEntity()
    {
        $entityClassName = 'Oro\Bundle\ReminderBundle\Entity\Reminder';
        $items           = [
            [
                'id'      => 1,
                'subject' => 'item1',
            ],
            [
                'id'      => 2,
                'subject' => 'item2',
            ],
        ];

        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $this->manager->applyReminders($items, $entityClassName);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testApplyReminders()
    {
        $entityClassName = 'Oro\Bundle\ReminderBundle\Tests\Unit\Fixtures\RemindableEntity';
        $items           = [
            [
                'id'      => 1,
                'subject' => 'item1',
            ],
            [
                'id'      => 2,
                'subject' => 'item2',
            ],
            [
                'id'      => 3,
                'subject' => 'item3',
            ],
        ];

        $query        = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb           = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $reminderRepo = $this->getReminderRepository();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->will($this->returnValue($reminderRepo));
        $reminderRepo->expects($this->once())
            ->method('findRemindersByEntitiesQueryBuilder')
            ->with($entityClassName, [1, 2, 3])
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('select')
            ->with('reminder.relatedEntityId, reminder.method, reminder.intervalNumber, reminder.intervalUnit')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will(
                $this->returnValue(
                    [
                        [
                            'relatedEntityId' => 3,
                            'method'          => 'email',
                            'intervalNumber'  => 1,
                            'intervalUnit'    => ReminderInterval::UNIT_HOUR
                        ],
                        [
                            'relatedEntityId' => 2,
                            'method'          => 'email',
                            'intervalNumber'  => 15,
                            'intervalUnit'    => ReminderInterval::UNIT_MINUTE
                        ],
                        [
                            'relatedEntityId' => 2,
                            'method'          => 'flash',
                            'intervalNumber'  => 10,
                            'intervalUnit'    => ReminderInterval::UNIT_MINUTE
                        ],
                    ]
                )
            );

        $this->manager->applyReminders($items, $entityClassName);
        $this->assertEquals(
            [
                [
                    'id'      => 1,
                    'subject' => 'item1',
                ],
                [
                    'id'        => 2,
                    'subject'   => 'item2',
                    'reminders' => [
                        [
                            'method'   => 'email',
                            'interval' => [
                                'number' => 15,
                                'unit'   => ReminderInterval::UNIT_MINUTE
                            ]
                        ],
                        [
                            'method'   => 'flash',
                            'interval' => [
                                'number' => 10,
                                'unit'   => ReminderInterval::UNIT_MINUTE
                            ]
                        ],
                    ]
                ],
                [
                    'id'        => 3,
                    'subject'   => 'item3',
                    'reminders' => [
                        [
                            'method'   => 'email',
                            'interval' => [
                                'number' => 1,
                                'unit'   => ReminderInterval::UNIT_HOUR
                            ]
                        ],
                    ]
                ],
            ],
            $items
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $reminder
     * @param string $entityClassName
     * @param int $entityId
     * @param ReminderDataInterface $reminderData
     */
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

    /**
     * @param int $id
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createReminder($id)
    {
        $result = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $result->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $result;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ReminderDataInterface
     */
    protected function createReminderData()
    {
        return $this->getMock('Oro\Bundle\ReminderBundle\Model\ReminderDataInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
