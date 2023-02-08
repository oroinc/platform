<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DraftBundle\EventListener\ORM\DraftSourceListener;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

class DraftSourceListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getSupportedPlatformDataProvider
     */
    public function testNotDraft(string $platform): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(\stdClass::class);

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $listener = new DraftSourceListener($platform);
        $listener->loadClassMetadata($event);
    }

    /**
     * @dataProvider getSupportedPlatformDataProvider
     */
    public function testHasAssociationDraftSource(string $platform): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(DraftableEntityStub::class);
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->with('draftSource')
            ->willReturn(true);
        $metadata->expects($this->never())
            ->method('getIdentifier');
        $metadata->expects($this->never())
            ->method('mapManyToOne');

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $listener = new DraftSourceListener($platform);
        $listener->loadClassMetadata($event);
    }

    /**
     * @dataProvider getSupportedPlatformDataProvider
     */
    public function testMapManyToOne(string $platform): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn(DraftableEntityStub::class);
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->with('draftSource')
            ->willReturn(false);
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(['id']);
        $metadata->expects($this->once())
            ->method('mapManyToOne')
            ->with($this->getPropertyMetadata());

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $listener = new DraftSourceListener($platform);
        $listener->loadClassMetadata($event);
    }

    public function getSupportedPlatformDataProvider(): array
    {
        return [
            [DatabaseDriverInterface::DRIVER_POSTGRESQL],
            [DatabaseDriverInterface::DRIVER_MYSQL]
        ];
    }

    private function getPropertyMetadata(): array
    {
        return [
            'joinColumns' => [[
                'name' => 'draft_source_id',
                'nullable' => true,
                'onDelete' => 'CASCADE',
                'columnDefinition' => null,
                'referencedColumnName' => 'id'
            ]],
            'isOwningSide' => true,
            'fieldName' => 'draftSource',
            'targetEntity' => DraftableEntityStub::class,
            'sourceEntity' => DraftableEntityStub::class,
            'fetch' => ClassMetadataInfo::FETCH_LAZY,
            'type' => ClassMetadataInfo::MANY_TO_ONE,
            'sourceToTargetKeyColumns' => ['draft_source_id' => 'id'],
            'joinColumnFieldNames' => ['draft_source_id' => 'draft_source_id'],
            'targetToSourceKeyColumns' => ['id' => 'draft_source_id']
        ];
    }
}
