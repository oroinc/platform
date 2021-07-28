<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use PHPUnit\Framework\MockObject\MockObject;

class ReminderManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|EntityManager */
    protected $entityManager;

    /** @var MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var ReminderManager */
    protected $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->method('getEntityManager')->willReturn($this->entityManager);

        $this->manager = new ReminderManager($this->doctrineHelper);
    }

    public function testSaveRemindersOnCreate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $reminders = new ArrayCollection([$fooReminder, $barReminder]);

        $reminderData = $this->createReminderData();

        $entityId = 101;
        /** @var MockObject|RemindableInterface $entity */
        $entity = $this->createMock(RemindableInterface::class);
        $entityClass = \get_class($entity);

        $entity->expects(static::once())->method('getReminders')->willReturn($reminders);
        $entity->expects(static::once())->method('getReminderData')->willReturn($reminderData);

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);
        $this->expectReminderSync($barReminder, $entityClass, $entityId, $reminderData);

        $this->doctrineHelper->expects(static::once())->method('getSingleEntityIdentifier')->willReturn($entityId);
        $this->doctrineHelper->expects(static::once())->method('getEntityClass')->willReturn($entityClass);

        $this->entityManager->expects(static::at(0))->method('persist')->with($fooReminder);
        $this->entityManager->expects(static::at(1))->method('persist')->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    public function testSaveEmptyEntityId()
    {
        /** @var MockObject|RemindableInterface $entity */
        $entity = $this->createMock(RemindableInterface::class);

        $this->doctrineHelper->expects(static::once())->method('getSingleEntityIdentifier')->willReturn(null);
        $this->entityManager->expects(static::never())->method('persist');

        $this->manager->saveReminders($entity);
    }

    public function testSaveRemindersOnUpdate()
    {
        $fooReminder = $this->createReminder(100);
        $barReminder = $this->createReminder(200);
        $remindersCollection = $this->getMockBuilder(RemindersPersistentCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remindersCollection->expects(static::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$fooReminder]));

        $remindersCollection->expects(static::once())->method('isDirty')->willReturn(true);
        $remindersCollection->expects(static::once())->method('getInsertDiff')->willReturn([$fooReminder]);
        $remindersCollection->expects(static::once())->method('getDeleteDiff')->willReturn([$barReminder]);

        $reminderData = $this->createReminderData();

        $entityId = 101;
        /** @var MockObject|RemindableInterface $entity */
        $entity = $this->createMock(RemindableInterface::class);
        $entityClass = get_class($entity);

        $entity->expects(static::once())->method('getReminders')->willReturn($remindersCollection);
        $entity->expects(static::once())->method('getReminderData')->willReturn($reminderData);

        $this->expectReminderSync($fooReminder, $entityClass, $entityId, $reminderData);

        $this->doctrineHelper->expects(static::once())->method('getSingleEntityIdentifier')->willReturn($entityId);
        $this->doctrineHelper->expects(static::once())->method('getEntityClass')->willReturn($entityClass);

        $this->entityManager->expects(static::at(0))->method('persist')->with($fooReminder);
        $this->entityManager->expects(static::at(1))->method('remove')->with($barReminder);

        $this->manager->saveReminders($entity);
    }

    /**
     * @dataProvider emptyRemindersProvider
     */
    public function testSaveRemindersEmpty($reminders)
    {
        $entityId = 101;
        /** @var MockObject|RemindableInterface $entity */
        $entity = $this->createMock(RemindableInterface::class);
        $entityClass = get_class($entity);

        $entity->expects(static::once())->method('getReminders')->willReturn($reminders);
        $entity->expects(static::never())->method('getReminderData');

        $this->doctrineHelper->expects(static::once())->method('getSingleEntityIdentifier')->willReturn($entityId);
        $this->doctrineHelper->expects(static::once())->method('getEntityClass')->willReturn($entityClass);

        $this->manager->saveReminders($entity);
    }

    public function emptyRemindersProvider()
    {
        $remindersPersistentCollection = $this->getMockBuilder(RemindersPersistentCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $remindersPersistentCollection->expects(static::any())->method('isEmpty')->willReturn(true);

        return [
            [$remindersPersistentCollection],
            [new ArrayCollection()],
            [[]],
        ];
    }

    public function testLoadReminders()
    {
        $entityId = 101;
        /** @var MockObject|RemindableInterface $entity */
        $entity = $this->createMock(RemindableInterface::class);
        $entityClass = get_class($entity);

        $repository = $this->getReminderRepository();

        $this->entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->willReturn($repository);

        $this->doctrineHelper->expects(static::once())->method('getSingleEntityIdentifier')->willReturn($entityId);
        $this->doctrineHelper->expects(static::once())->method('getEntityClass')->willReturn($entityClass);

        $entity->expects(static::once())
            ->method('setReminders')
            ->with(
                static::callback(
                    function ($reminders) {
                        static::assertInstanceOf(RemindersPersistentCollection::class, $reminders);
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

        $this->entityManager->expects(static::never())->method('getRepository');

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

        $this->entityManager->expects(static::never())->method('getRepository');

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

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $reminderRepo = $this->getReminderRepository();
        $this->entityManager->expects(static::once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->willReturn($reminderRepo);
        $reminderRepo->expects(static::once())
            ->method('findRemindersByEntitiesQueryBuilder')
            ->with($entityClassName, [1, 2, 3])
            ->willReturn($qb);
        $qb->expects(static::once())
            ->method('select')
            ->with('reminder.relatedEntityId, reminder.method, reminder.intervalNumber, reminder.intervalUnit')
            ->willReturnSelf();
        $qb->expects(static::once())->method('getQuery')->willReturn($query);
        $query->expects(static::once())
            ->method('getArrayResult')
            ->willReturn(
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
            );

        $this->manager->applyReminders($items, $entityClassName);
        static::assertEquals(
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
     * @param MockObject $reminder
     * @param string $entityClassName
     * @param int $entityId
     * @param ReminderDataInterface $reminderData
     */
    protected function expectReminderSync(
        MockObject $reminder,
        $entityClassName,
        $entityId,
        ReminderDataInterface $reminderData
    ) {
        $reminder->expects(static::once())->method('setRelatedEntityClassName')->with($entityClassName);
        $reminder->expects(static::once())->method('setRelatedEntityId')->with($entityId);
        $reminder->expects(static::once())->method('setReminderData')->with($reminderData);
    }

    /**
     * @param int $id
     * @return MockObject
     */
    protected function createReminder($id)
    {
        $result = $this->createMock(Reminder::class);
        $result->expects(static::any())->method('getId')->willReturn($id);

        return $result;
    }

    /**
     * @return MockObject|ReminderDataInterface
     */
    protected function createReminderData()
    {
        return $this->createMock(ReminderDataInterface::class);
    }

    /**
     * @return MockObject|ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->getMockBuilder(ReminderRepository::class)->disableOriginalConstructor()->getMock();
    }
}
