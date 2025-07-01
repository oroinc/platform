<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityDefinitionFieldConfigTest extends TestCase
{
    public function testCustomAttribute(): void
    {
        $attrName = 'test';

        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testExcluded(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals([], $config->toArray());
    }

    public function testPropertyPath(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        self::assertTrue($config->hasPropertyPath());
        self::assertEquals('path', $config->getPropertyPath());
        self::assertEquals('path', $config->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));
        self::assertEquals([], $config->toArray());
    }

    public function testDescription(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());

        $config->setDescription('text');
        self::assertTrue($config->hasDescription());
        self::assertEquals('text', $config->getDescription());
        self::assertEquals(['description' => 'text'], $config->toArray());

        $config->setDescription(null);
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());

        $config->setDescription('text');
        $config->setDescription('');
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());
    }

    public function testDataType(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());
        self::assertTrue($config->isEmpty());

        $config->setDataType('string');
        self::assertTrue($config->hasDataType());
        self::assertEquals('string', $config->getDataType());
        self::assertFalse($config->isEmpty());

        $config->setDataType(null);
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());
        self::assertTrue($config->isEmpty());
    }

    public function testDirection(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());

        $config->setDirection('input-only');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertFalse($config->isOutput());
        self::assertEquals(['direction' => 'input-only'], $config->toArray());

        $config->setDirection('output-only');
        self::assertTrue($config->hasDirection());
        self::assertFalse($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'output-only'], $config->toArray());

        $config->setDirection('bidirectional');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'bidirectional'], $config->toArray());

        $config->setDirection(null);
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals([], $config->toArray());
    }

    public function testSetInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The possible values for the direction are "input-only", "output-only" or "bidirectional".'
        );

        $config = new EntityDefinitionFieldConfig();

        $config->setDirection('another');
    }

    public function testMetaProperty(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->isMetaProperty());

        $config->setMetaProperty(true);
        self::assertTrue($config->isMetaProperty());
        self::assertEquals(['meta_property' => true], $config->toArray());

        $config->setMetaProperty(false);
        self::assertFalse($config->isMetaProperty());
        self::assertEquals([], $config->toArray());
    }

    public function testGetOrCreateTargetEntity(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetEntity());
        self::assertNull($config->getTargetEntity());

        $targetEntity = $config->getOrCreateTargetEntity();
        self::assertTrue($config->hasTargetEntity());
        self::assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->getOrCreateTargetEntity();
        self::assertSame($targetEntity, $targetEntity1);
        self::assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testMetaPropertyResultName()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getMetaPropertyResultName());

        $config->setMetaPropertyResultName('test');
        self::assertEquals('test', $config->getMetaPropertyResultName());
        self::assertEquals(['meta_property_result_name' => 'test'], $config->toArray());

        $config->setMetaPropertyResultName(null);
        self::assertNull($config->getMetaPropertyResultName());
        self::assertEquals([], $config->toArray());
    }

    public function testAssociationLevelMetaProperty()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->isAssociationLevelMetaProperty());

        $config->setAssociationLevelMetaProperty(true);
        self::assertTrue($config->isAssociationLevelMetaProperty());
        self::assertEquals([], $config->toArray());

        $config->setAssociationLevelMetaProperty(false);
        self::assertFalse($config->isAssociationLevelMetaProperty());
        self::assertEquals([], $config->toArray());
    }

    public function testCreateAndSetTargetEntity(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetEntity());
        self::assertNull($config->getTargetEntity());

        $targetEntity = $config->createAndSetTargetEntity();
        self::assertTrue($config->hasTargetEntity());
        self::assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->createAndSetTargetEntity();
        self::assertNotSame($targetEntity, $targetEntity1);
        self::assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testTargetClass(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getTargetClass());

        $config->setTargetClass('Test\Class');
        self::assertEquals('Test\Class', $config->getTargetClass());
        self::assertEquals(['target_class' => 'Test\Class'], $config->toArray());

        $config->setTargetClass(null);
        self::assertNull($config->getTargetClass());
        self::assertEquals([], $config->toArray());
    }

    public function testTargetType(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetType());
        self::assertNull($config->getTargetType());
        self::assertFalse($config->isCollectionValuedAssociation());

        $config->setTargetType('to-one');
        self::assertTrue($config->hasTargetType());
        self::assertEquals('to-one', $config->getTargetType());
        self::assertFalse($config->isCollectionValuedAssociation());
        self::assertEquals(['target_type' => 'to-one'], $config->toArray());

        $config->setTargetType('to-many');
        self::assertTrue($config->hasTargetType());
        self::assertEquals('to-many', $config->getTargetType());
        self::assertTrue($config->isCollectionValuedAssociation());
        self::assertEquals(['target_type' => 'to-many'], $config->toArray());

        $config->setTargetType(null);
        self::assertFalse($config->hasTargetType());
        self::assertNull($config->getTargetType());
        self::assertFalse($config->isCollectionValuedAssociation());
        self::assertEquals([], $config->toArray());
    }

    public function testCollapsed(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasCollapsed());
        self::assertFalse($config->isCollapsed());

        $config->setCollapsed();
        self::assertTrue($config->hasCollapsed());
        self::assertTrue($config->isCollapsed());
        self::assertEquals(['collapse' => true], $config->toArray());

        $config->setCollapsed(false);
        self::assertTrue($config->hasCollapsed());
        self::assertFalse($config->isCollapsed());
        self::assertEquals([], $config->toArray());
    }

    public function testFormType(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());
        self::assertSame('val', $config->getFormOption('key'));
        self::assertSame('val', $config->getFormOption('key', ''));

        $config->setFormOptions([]);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));
    }

    public function testSetFormOption(): void
    {
        $config = new EntityDefinitionFieldConfig();

        $config->setFormOption('option1', 'value1');
        $config->setFormOption('option2', 'value2');
        self::assertEquals(
            ['option1' => 'value1', 'option2' => 'value2'],
            $config->getFormOptions()
        );

        $config->setFormOption('option1', 'newValue');
        self::assertEquals(
            ['option1' => 'newValue', 'option2' => 'value2'],
            $config->getFormOptions()
        );
    }

    public function testFormConstraints(): void
    {
        $config = new EntityDefinitionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }

    public function testRemoveFormConstraint(): void
    {
        $config = new EntityDefinitionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->removeFormConstraint(NotNull::class);
        self::assertNull($config->getFormConstraints());

        $config->setFormOption(
            'constraints',
            [
                new NotNull(),
                new NotBlank(),
                [NotNull::class => ['message' => 'test']]
            ]
        );

        $config->removeFormConstraint(NotNull::class);
        self::assertEquals(['constraints' => [new NotBlank()]], $config->getFormOptions());

        $config->removeFormConstraint(NotBlank::class);
        self::assertNull($config->getFormOptions());
    }

    public function testPostProcessor(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());

        $config->setPostProcessor('test');
        self::assertTrue($config->hasPostProcessor());
        self::assertEquals('test', $config->getPostProcessor());
        self::assertEquals(['post_processor' => 'test'], $config->toArray());

        $config->setPostProcessor(null);
        self::assertTrue($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());
        self::assertEquals(['post_processor' => null], $config->toArray());

        $config->removePostProcessor();
        self::assertFalse($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());
        self::assertEquals([], $config->toArray());
    }

    public function testPostProcessorOptions(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getPostProcessorOptions());

        $config->setPostProcessorOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getPostProcessorOptions());
        self::assertEquals(['post_processor_options' => ['key' => 'val']], $config->toArray());

        $config->setPostProcessorOptions(null);
        self::assertNull($config->getPostProcessorOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetDataTransformers(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDataTransformers());
        self::assertEquals([], $config->getDataTransformers());

        $config->setDataTransformers('service_id');
        self::assertTrue($config->hasDataTransformers());
        self::assertEquals(['service_id'], $config->getDataTransformers());
        self::assertEquals(['data_transformer' => ['service_id']], $config->toArray());

        $config->setDataTransformers(['service_id', ['class', 'method']]);
        self::assertTrue($config->hasDataTransformers());
        self::assertEquals(['service_id', ['class', 'method']], $config->getDataTransformers());
        self::assertEquals(['data_transformer' => ['service_id', ['class', 'method']]], $config->toArray());

        $config->setDataTransformers([]);
        self::assertFalse($config->hasDataTransformers());
        self::assertEquals([], $config->getDataTransformers());
        self::assertEquals([], $config->toArray());
    }

    public function testDependsOn(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getDependsOn());

        $config->setDependsOn(['field1']);
        self::assertEquals(['field1'], $config->getDependsOn());
        self::assertEquals(['depends_on' => ['field1']], $config->toArray());

        $config->setDependsOn([]);
        self::assertNull($config->getDependsOn());
        self::assertEquals([], $config->toArray());

        $config->addDependsOn('field1');
        self::assertEquals(['field1'], $config->getDependsOn());
        self::assertEquals(['depends_on' => ['field1']], $config->toArray());

        $config->addDependsOn('field2');
        self::assertEquals(['field1', 'field2'], $config->getDependsOn());
        self::assertEquals(['depends_on' => ['field1', 'field2']], $config->toArray());

        $config->addDependsOn('field1');
        self::assertEquals(['field1', 'field2'], $config->getDependsOn());
        self::assertEquals(['depends_on' => ['field1', 'field2']], $config->toArray());
    }

    public function testAssociationQuery(): void
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getAssociationQuery());

        $query = $this->createMock(QueryBuilder::class);
        $config->setTargetClass('Test\Class');
        $config->setTargetType('to-many');
        $config->setAssociationQuery($query);
        self::assertSame($query, $config->getAssociationQuery());
        self::assertEquals($query, $config->get(ConfigUtil::ASSOCIATION_QUERY));

        $config->setAssociationQuery(null);
        self::assertNull($config->getAssociationQuery());
        self::assertFalse($config->has(ConfigUtil::ASSOCIATION_QUERY));
    }

    public function testSetAssociationQueryWhenNoTargetClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The target class must be specified to be able to use an association query.');

        $config = new EntityDefinitionFieldConfig();
        $config->setAssociationQuery($this->createMock(QueryBuilder::class));
    }
}
