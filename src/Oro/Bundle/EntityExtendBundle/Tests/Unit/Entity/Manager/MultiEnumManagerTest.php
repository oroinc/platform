<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\MultiEnumManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Filter\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Filter\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class MultiEnumManagerTest extends \PHPUnit_Framework_TestCase
{
    const ENUM_VALUE_CLASS = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Filter\TestEnumValue';

    /** @var MultiEnumManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    protected function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->manager = new MultiEnumManager();
    }

    public function testHandleOnFlushWithNoChangesInCollections()
    {
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->will($this->returnValue([]));

        $this->uow->expects($this->never())
            ->method('recomputeSingleEntityChangeSet');

        $event = $this->getOnFlushEventArgsMock();
        $this->manager->handleOnFlush($event);
    }

    public function testHandleOnFlushWithNothingToChange()
    {
        $collectionUpdates = [
            $this->getPersistentCollection(
                new \stdClass(),
                [
                    'type'         => ClassMetadata::MANY_TO_ONE,
                    'isOwningSide' => true,
                    'fieldName'    => 'values',
                    'targetEntity' => self::ENUM_VALUE_CLASS
                ],
                [
                    new TestEnumValue('val1', 'Value 1')
                ]
            ),
            $this->getPersistentCollection(
                new \stdClass(),
                [
                    'type'         => ClassMetadata::MANY_TO_MANY,
                    'isOwningSide' => true,
                    'fieldName'    => 'values',
                    'targetEntity' => 'Test\TargetEntity'
                ],
                ['val1']
            ),
        ];

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue($collectionUpdates));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->will($this->returnValue([]));

        $this->uow->expects($this->never())
            ->method('recomputeSingleEntityChangeSet');

        $event = $this->getOnFlushEventArgsMock();
        $this->manager->handleOnFlush($event);
    }

    public function testHandleOnFlush()
    {
        $owner = new TestEntity();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'values',
                'targetEntity' => self::ENUM_VALUE_CLASS
            ],
            [
                new TestEnumValue('val2', 'Value 2'),
                new TestEnumValue('val1', 'Value 1'),
                new TestEnumValue('val3', 'Value 3'),
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([$updatedColl]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->will($this->returnValue([]));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->will($this->returnValue($metadata));

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(
                $this->identicalTo($metadata),
                $owner
            );

        $event = $this->getOnFlushEventArgsMock();
        $this->manager->handleOnFlush($event);

        $this->assertEquals(
            'val1,val2,val3',
            $owner->getValuesSnapshot()
        );
    }

    public function testHandleOnFlushWhenSnapshotLengthIsNotEnough()
    {
        $owner = new TestEntity();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'values',
                'targetEntity' => self::ENUM_VALUE_CLASS
            ],
            [
                new TestEnumValue('value678901234567890123456789_01', 'Value 1'),
                new TestEnumValue('value678901234567890123456789_02', 'Value 2'),
                new TestEnumValue('value678901234567890123456789_03', 'Value 3'),
                new TestEnumValue('value678901234567890123456789_04', 'Value 4'),
                new TestEnumValue('value678901234567890123456789_05', 'Value 5'),
                new TestEnumValue('value678901234567890123456789_06', 'Value 6'),
                new TestEnumValue('value678901234567890123456789_07', 'Value 7'),
                new TestEnumValue('value678901234567890123456789_08', 'Value 8'),
                new TestEnumValue('value678901234567890123456789_09', 'Value 9'),
                new TestEnumValue('value678901234567890123456789_10', 'Value 10'),
                new TestEnumValue('value678901234567890123456789_11', 'Value 11'),
                new TestEnumValue('value678901234567890123456789_12', 'Value 12'),
                new TestEnumValue('value678901234567890123456789_13', 'Value 13'),
                new TestEnumValue('value678901234567890123456789_14', 'Value 14'),
                new TestEnumValue('value678901234567890123456789_15', 'Value 15'),
                new TestEnumValue('value678901234567890123456789_16', 'Value 16'),
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([$updatedColl]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->will($this->returnValue([]));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->will($this->returnValue($metadata));

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(
                $this->identicalTo($metadata),
                $owner
            );

        $event = $this->getOnFlushEventArgsMock();
        $this->manager->handleOnFlush($event);

        $this->assertLessThanOrEqual(
            ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
            strlen($owner->getValuesSnapshot())
        );
        $this->assertEquals(
            'value678901234567890123456789_01,value678901234567890123456789_02,value678901234567890123456789_03,'
            . 'value678901234567890123456789_04,value678901234567890123456789_05,value678901234567890123456789_06,'
            . 'value678901234567890123456789_07,value678901234567890123456789_08,value678901234567890123456789_09,'
            . 'value678901234567890123456789_10,value678901234567890123456789_11,value678901234567890123456789_12,'
            . 'value678901234567890123456789_13,value678901234567890123456789_14,value678901234567890123456789_15,...',
            $owner->getValuesSnapshot()
        );
    }

    public function testHandleOnFlushWhenSnapshotLengthIsNotEnoughAndTwoValuesAreReplacedWithDots()
    {
        $owner = new TestEntity();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'values',
                'targetEntity' => self::ENUM_VALUE_CLASS
            ],
            [
                new TestEnumValue('value678901234567890123456789_01', 'Value 1'),
                new TestEnumValue('value678901234567890123456789_02', 'Value 2'),
                new TestEnumValue('value678901234567890123456789_03', 'Value 3'),
                new TestEnumValue('value678901234567890123456789_04', 'Value 4'),
                new TestEnumValue('value678901234567890123456789_05', 'Value 5'),
                new TestEnumValue('value678901234567890123456789_06', 'Value 6'),
                new TestEnumValue('value678901234567890123456789_07', 'Value 7'),
                new TestEnumValue('value678901234567890123456789_08', 'Value 8'),
                new TestEnumValue('value678901234567890123456789_09', 'Value 9'),
                new TestEnumValue('value678901234567890123456789_10', 'Value 10'),
                new TestEnumValue('value678901234567890123456789_11', 'Value 11'),
                new TestEnumValue('value678901234567890123456789_12', 'Value 12'),
                new TestEnumValue('value678901234567890123456789_13', 'Value 13'),
                new TestEnumValue('value678901234567890123456789_14', 'Value 14'),
                new TestEnumValue('value678901234567890123456789_15', 'Value 15'),
                new TestEnumValue('z1', 'Value 16'),
                new TestEnumValue('zz1', 'Value 17'),
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([$updatedColl]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->will($this->returnValue([]));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->will($this->returnValue($metadata));

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(
                $this->identicalTo($metadata),
                $owner
            );

        $event = $this->getOnFlushEventArgsMock();
        $this->manager->handleOnFlush($event);

        $this->assertLessThanOrEqual(
            ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
            strlen($owner->getValuesSnapshot())
        );
        $this->assertEquals(
            'value678901234567890123456789_01,value678901234567890123456789_02,value678901234567890123456789_03,'
            . 'value678901234567890123456789_04,value678901234567890123456789_05,value678901234567890123456789_06,'
            . 'value678901234567890123456789_07,value678901234567890123456789_08,value678901234567890123456789_09,'
            . 'value678901234567890123456789_10,value678901234567890123456789_11,value678901234567890123456789_12,'
            . 'value678901234567890123456789_13,value678901234567890123456789_14,value678901234567890123456789_15,...',
            $owner->getValuesSnapshot()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getOnFlushEventArgsMock()
    {
        $flushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $flushEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        return $flushEventArgs;
    }

    /**
     * @param object $owner
     * @param array  $mapping
     * @param array  $items
     *
     * @return PersistentCollection
     */
    protected function getPersistentCollection($owner, array $mapping, array $items = [])
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $coll     = new PersistentCollection(
            $this->em,
            $metadata,
            new ArrayCollection($items)
        );

        $mapping['inversedBy'] = 'test';
        $coll->setOwner($owner, $mapping);

        return $coll;
    }
}
