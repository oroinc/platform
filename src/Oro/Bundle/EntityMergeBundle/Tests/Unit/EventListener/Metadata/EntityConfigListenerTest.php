<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigHelper;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigListener;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var EntityConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(EntityConfigHelper::class);

        $this->listener = new EntityConfigListener($this->helper);
    }

    private function createConfig(array $options): ConfigInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('all')
            ->willReturn($options);

        return $config;
    }

    public function testOnCreateMetadata()
    {
        $className = 'Foo\Entity';

        $entityMetadata = $this->createMock(EntityMetadata::class);

        $entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $entityMergeOptions = ['enable' => true];
        $entityMergeConfig = $this->createConfig($entityMergeOptions);

        $entityMetadata->expects($this->once())
            ->method('merge')
            ->with($entityMergeOptions);

        $fooFieldMetadata = $this->createMock(FieldMetadata::class);
        $barFieldMetadata = $this->createMock(FieldMetadata::class);
        $entityMetadata->expects($this->once())
            ->method('getFieldsMetadata')
            ->willReturn([$fooFieldMetadata, $barFieldMetadata]);

        $fooEntityMergeOptions = [
            'display' => false,
            'inverse_display' => true,
            'property_path' => 'test',
        ];
        $expectedFooEntityMergeOptions = [
            'display' => false,
            'property_path' => 'test',
        ];
        $fooEntityMergeConfig = $this->createConfig($fooEntityMergeOptions);

        $fooFieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(true);

        $fooFieldMetadata->expects($this->once())
            ->method('merge')
            ->with($expectedFooEntityMergeOptions);

        $barEntityMergeOptions = [
            'display' => false,
            'inverse_display' => true,
            'property_path' => 'test',
        ];
        $expectedBarEntityMergeOptions = [
            'display' => true,
            'property_path' => 'test',
        ];
        $barEntityMergeConfig = $this->createConfig($barEntityMergeOptions);

        $barFieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);
        $barFieldMetadata->expects($this->once())
            ->method('merge')
            ->with($expectedBarEntityMergeOptions);

        $this->helper->expects($this->once())
            ->method('getConfig')
            ->with(EntityConfigListener::CONFIG_MERGE_SCOPE, $className, null)
            ->willReturn($entityMergeConfig);
        $this->helper->expects($this->exactly(2))
            ->method('getConfigByFieldMetadata')
            ->withConsecutive(
                [EntityConfigListener::CONFIG_MERGE_SCOPE, $this->identicalTo($fooFieldMetadata)],
                [EntityConfigListener::CONFIG_MERGE_SCOPE, $this->identicalTo($barFieldMetadata)]
            )
            ->willReturnOnConsecutiveCalls(
                $fooEntityMergeConfig,
                $barEntityMergeConfig
            );
        $this->helper->expects($this->exactly(2))
            ->method('prepareFieldMetadataPropertyPath')
            ->withConsecutive(
                [$this->identicalTo($fooFieldMetadata)],
                [$this->identicalTo($barFieldMetadata)]
            );

        $this->listener->onCreateMetadata(new EntityMetadataEvent($entityMetadata));
    }
}
