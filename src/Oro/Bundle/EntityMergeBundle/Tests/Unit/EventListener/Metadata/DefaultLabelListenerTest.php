<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\DefaultLabelListener;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class DefaultLabelListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Namespace\Entity';

    /** @var EntityConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigHelper;

    /** @var DefaultLabelListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityConfigHelper = $this->createMock(EntityConfigHelper::class);

        $this->listener = new DefaultLabelListener($this->entityConfigHelper);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnCreateMetadata()
    {
        $entityMetadata = $this->createMock(EntityMetadata::class);
        $entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn(self::ENTITY_CLASS);

        $entityMetadata->expects($this->once())
            ->method('has')
            ->with('label')
            ->willReturn(false);

        $entityConfig = $this->createMock(ConfigInterface::class);

        $expectedEntityPluralLabel = 'entity_plural_label';
        $entityConfig->expects($this->once())
            ->method('get')
            ->with('plural_label')
            ->willReturn($expectedEntityPluralLabel);

        $entityMetadata->expects($this->once())
            ->method('set')
            ->with('label', $expectedEntityPluralLabel);

        $fooField = $this->createMock(FieldMetadata::class);
        $barField = $this->createMock(FieldMetadata::class);
        $bazField = $this->createMock(FieldMetadata::class);

        $entityMetadata->expects($this->once())
            ->method('getFieldsMetadata')
            ->willReturn([$fooField, $barField, $bazField]);

        // Field with label
        $fooField->expects($this->once())
            ->method('has')
            ->with('label')
            ->willReturn(true);

        // Field not defined by source entity and collection
        $barField->expects($this->once())
            ->method('has')
            ->with('label')
            ->willReturn(false);

        $barExpectedSourceClassName = 'Bar\Entity';
        $barField->expects($this->once())
            ->method('getSourceClassName')
            ->willReturn($barExpectedSourceClassName);

        $barExpectedSourceFieldName = 'bar_source_field_name';
        $barField->expects($this->once())
            ->method('getSourceFieldName')
            ->willReturn($barExpectedSourceFieldName);

        $barField->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);
        $barField->expects($this->once())
            ->method('isCollection')
            ->willReturn(true);

        $barFieldEntityConfig = $this->createMock(ConfigInterface::class);

        $barExpectedFieldLabel = 'bar_expected_field_label';

        $barFieldEntityConfig->expects($this->once())
            ->method('get')
            ->with('plural_label')
            ->willReturn($barExpectedFieldLabel);

        $barField->expects($this->once())
            ->method('set')
            ->with('label', $barExpectedFieldLabel);

        // Field defined by source entity
        $bazField->expects($this->once())
            ->method('has')
            ->with('label')
            ->willReturn(false);

        $bazExpectedSourceClassName = 'Baz\Entity';
        $bazField->expects($this->once())
            ->method('getSourceClassName')
            ->willReturn($bazExpectedSourceClassName);

        $bazExpectedSourceFieldName = 'baz_source_field_name';
        $bazField->expects($this->once())
            ->method('getSourceFieldName')
            ->willReturn($bazExpectedSourceFieldName);
        $bazField->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(true);

        $bazFieldEntityConfig = $this->createMock(ConfigInterface::class);

        $bazExpectedFieldLabel = 'bar_expected_field_label';

        $bazFieldEntityConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($bazExpectedFieldLabel);

        $bazField->expects($this->once())
            ->method('set')
            ->with('label', $bazExpectedFieldLabel);

        $this->entityConfigHelper->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturnMap([
                ['entity', self::ENTITY_CLASS, null, $entityConfig],
                ['entity', $barExpectedSourceClassName, null, $barFieldEntityConfig],
                ['entity', $bazExpectedSourceClassName, $bazExpectedSourceFieldName, $bazFieldEntityConfig]
            ]);

        $this->listener->onCreateMetadata(new EntityMetadataEvent($entityMetadata));
    }
}
