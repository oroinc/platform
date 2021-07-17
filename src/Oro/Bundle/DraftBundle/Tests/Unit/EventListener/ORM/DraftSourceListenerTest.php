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
     * @var LoadClassMetadataEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var DraftSourceListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(LoadClassMetadataEventArgs::class);

        $this->listener = new DraftSourceListener(
            DatabaseDriverInterface::DRIVER_POSTGRESQL
        );
    }

    public function testPlatformNotSupported(): void
    {
        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $listener = new DraftSourceListener('not_pgsql');
        $listener->loadClassMetadata($this->event);
    }

    public function testNotDraft(): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new \stdClass()));

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->listener->loadClassMetadata($this->event);
    }

    public function testHasAssociationDraftSource(): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new DraftableEntityStub()));
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->with('draftSource')
            ->willReturn(true);
        $metadata->expects($this->never())
            ->method('getName');
        $metadata->expects($this->never())
            ->method('getIdentifier');
        $metadata->expects($this->never())
            ->method('mapManyToOne');

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->listener->loadClassMetadata($this->event);
    }

    public function testMapManyToOne(): void
    {
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new DraftableEntityStub()));
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->with('draftSource')
            ->willReturn(false);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(DraftableEntityStub::class);
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(['id']);
        $metadata->expects($this->once())
            ->method('mapManyToOne')
            ->with($this->getPropertyMetadata());

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->listener->loadClassMetadata($this->event);
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
