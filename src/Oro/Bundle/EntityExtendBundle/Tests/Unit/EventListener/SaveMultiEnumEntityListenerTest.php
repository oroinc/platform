<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityExtendBundle\EventListener\SaveMultiEnumEntityListener;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEntityWithEnum;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class SaveMultiEnumEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var SaveMultiEnumEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->uow = $this->createMock(UnitOfWork::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new SaveMultiEnumEntityListener((new InflectorFactory())->build());
    }

    private function getPersistentCollection(object $owner, array $mapping, array $items = []): PersistentCollection
    {
        $coll = new PersistentCollection(
            $this->em,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection($items)
        );

        $mapping['inversedBy'] = 'test';
        $coll->setOwner($owner, $mapping);

        return $coll;
    }

    public function testHandleOnFlushWithNoChangesInCollections()
    {
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);

        $this->uow->expects($this->never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testHandleOnFlushWithNothingToChange()
    {
        $collectionUpdates = [
            $this->getPersistentCollection(
                new \stdClass(),
                [
                    'type'         => ClassMetadata::MANY_TO_ONE,
                    'isOwningSide' => true,
                    'fieldName'    => 'multipleEnumField',
                    'targetEntity' => TestEnumValue::class
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
                    'fieldName'    => 'multipleEnumField',
                    'targetEntity' => 'Test\TargetEntity'
                ],
                ['val1']
            ),
        ];

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn($collectionUpdates);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);

        $this->uow->expects($this->never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testHandleOnFlush()
    {
        $owner = new TestEntityWithEnum();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'multipleEnumField',
                'targetEntity' => TestEnumValue::class
            ],
            [
                new TestEnumValue('val2', 'Value 2'),
                new TestEnumValue('val1', 'Value 1'),
                new TestEnumValue('val3', 'Value 3'),
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$updatedColl]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->willReturn($metadata);

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(
                $this->identicalTo($metadata),
                $owner
            );

        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        $this->assertEquals(
            'val1,val2,val3',
            $owner->getMultipleEnumFieldSnapshot()
        );
    }

    public function testHandleOnFlushWhenSnapshotLengthIsNotEnough()
    {
        $owner = new TestEntityWithEnum();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'multipleEnumField',
                'targetEntity' => TestEnumValue::class
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
            ->willReturn([$updatedColl]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->willReturn($metadata);

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(
                $this->identicalTo($metadata),
                $owner
            );

        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        $this->assertLessThanOrEqual(
            ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
            strlen($owner->getMultipleEnumFieldSnapshot())
        );
        $this->assertEquals(
            'value678901234567890123456789_01,value678901234567890123456789_02,value678901234567890123456789_03,'
            . 'value678901234567890123456789_04,value678901234567890123456789_05,value678901234567890123456789_06,'
            . 'value678901234567890123456789_07,value678901234567890123456789_08,value678901234567890123456789_09,'
            . 'value678901234567890123456789_10,value678901234567890123456789_11,value678901234567890123456789_12,'
            . 'value678901234567890123456789_13,value678901234567890123456789_14,value678901234567890123456789_15,...',
            $owner->getMultipleEnumFieldSnapshot()
        );
    }

    public function testHandleOnFlushWhenSnapshotLengthIsNotEnoughAndTwoValuesAreReplacedWithDots()
    {
        $owner = new TestEntityWithEnum();
        $updatedColl = $this->getPersistentCollection(
            $owner,
            [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'isOwningSide' => true,
                'fieldName'    => 'multipleEnumField',
                'targetEntity' => TestEnumValue::class
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
            ->willReturn([$updatedColl]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($owner))
            ->willReturn($metadata);

        $this->uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($this->identicalTo($metadata), $owner);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        $this->assertLessThanOrEqual(
            ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
            strlen($owner->getMultipleEnumFieldSnapshot())
        );
        $this->assertEquals(
            'value678901234567890123456789_01,value678901234567890123456789_02,value678901234567890123456789_03,'
            . 'value678901234567890123456789_04,value678901234567890123456789_05,value678901234567890123456789_06,'
            . 'value678901234567890123456789_07,value678901234567890123456789_08,value678901234567890123456789_09,'
            . 'value678901234567890123456789_10,value678901234567890123456789_11,value678901234567890123456789_12,'
            . 'value678901234567890123456789_13,value678901234567890123456789_14,value678901234567890123456789_15,...',
            $owner->getMultipleEnumFieldSnapshot()
        );
    }
}
