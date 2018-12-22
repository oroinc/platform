<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class EntityDefinitionConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testKey()
    {
        $config = new EntityDefinitionConfig();
        self::assertNull($config->getKey());

        $config->setKey('text');
        self::assertEquals('text', $config->getKey());
        self::assertEquals([], $config->toArray());

        $config->setKey(null);
        self::assertNull($config->getKey());
    }

    public function testClone()
    {
        $config = new EntityDefinitionConfig();
        $config->setKey('some key');
        $config->setExcludeAll();
        $config->set('test_scalar', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);
        $config->addField('field1')->setDataType('int');

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new EntityDefinitionConfig();
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

    public function testParentResourceClass()
    {
        $config = new EntityDefinitionConfig();
        self::assertNull($config->getParentResourceClass());

        $config->setParentResourceClass('Test\Class');
        self::assertEquals('Test\Class', $config->getParentResourceClass());
        self::assertEquals(['parent_resource_class' => 'Test\Class'], $config->toArray());

        $config->setParentResourceClass(null);
        self::assertNull($config->getParentResourceClass());
        self::assertEquals([], $config->toArray());

        $config->setParentResourceClass('Test\Class');
        $config->setParentResourceClass(null);
        self::assertNull($config->getParentResourceClass());
        self::assertEquals([], $config->toArray());
    }

    public function testDescription()
    {
        $config = new EntityDefinitionConfig();
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

    public function testDocumentation()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());

        $config->setDocumentation('text');
        self::assertTrue($config->hasDocumentation());
        self::assertEquals('text', $config->getDocumentation());
        self::assertEquals(['documentation' => 'text'], $config->toArray());

        $config->setDocumentation(null);
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());
        self::assertEquals([], $config->toArray());

        $config->setDocumentation('text');
        $config->setDocumentation('');
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());
        self::assertEquals([], $config->toArray());
    }

    public function testDocumentationResources()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDocumentationResources());
        self::assertSame([], $config->getDocumentationResources());

        $config->setDocumentationResources(['resource link']);
        self::assertTrue($config->hasDocumentationResources());
        self::assertEquals(['resource link'], $config->getDocumentationResources());
        self::assertEquals(['documentation_resource' => ['resource link']], $config->toArray());

        $config->setDocumentationResources('another resource link');
        self::assertTrue($config->hasDocumentationResources());
        self::assertEquals(['another resource link'], $config->getDocumentationResources());
        self::assertEquals(['documentation_resource' => ['another resource link']], $config->toArray());

        $config->setDocumentationResources(null);
        self::assertFalse($config->hasDocumentationResources());
        self::assertSame([], $config->getDocumentationResources());
        self::assertEquals([], $config->toArray());

        $config->setDocumentationResources(['resource link']);
        $config->setDocumentationResources([]);
        self::assertFalse($config->hasDocumentationResources());
        self::assertSame([], $config->getDocumentationResources());
        self::assertEquals([], $config->toArray());
    }

    public function testAclResource()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasAclResource());
        self::assertNull($config->getAclResource());

        $config->setAclResource('test_acl_resource');
        self::assertTrue($config->hasAclResource());
        self::assertEquals('test_acl_resource', $config->getAclResource());
        self::assertEquals(['acl_resource' => 'test_acl_resource'], $config->toArray());

        $config->setAclResource(null);
        self::assertTrue($config->hasAclResource());
        self::assertNull($config->getAclResource());
        self::assertEquals(['acl_resource' => null], $config->toArray());
    }

    public function testFields()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasFields());
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());

        $field = $config->addField('field');
        self::assertTrue($config->hasFields());
        self::assertEquals(['field' => $field], $config->getFields());
        self::assertSame($field, $config->getField('field'));
        self::assertFalse($config->isEmpty());
        self::assertEquals(['fields' => ['field' => null]], $config->toArray());

        $config->removeField('field');
        self::assertFalse($config->hasFields());
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
    }

    public function testFindField()
    {
        $config = new EntityDefinitionConfig();

        $field1 = $config->addField('field1');
        $field2 = $config->addField('field2');
        $field2->setPropertyPath('realField2');
        $field3 = $config->addField('field3');
        $field3->setPropertyPath('field3');
        $swapField = $config->addField('swapField');
        $swapField->setPropertyPath('realSwapField');
        $realSwapField = $config->addField('realSwapField');
        $realSwapField->setPropertyPath('swapField');
        $ignoredField = $config->addField('ignoredField');
        $ignoredField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $replacedField = $config->addField('replacedField');
        $replacedField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $realReplacedField = $config->addField('realReplacedField');
        $realReplacedField->setPropertyPath('replacedField');

        self::assertNull($config->findField('unknown'));
        self::assertNull($config->findField('unknown', true));
        self::assertNull($config->findFieldNameByPropertyPath('unknown'));
        self::assertNull($config->findFieldByPath('unknown'));
        self::assertNull($config->findFieldByPath('unknown', true));

        self::assertSame($field1, $config->findField('field1'));
        self::assertSame($field1, $config->findField('field1', true));
        self::assertSame('field1', $config->findFieldNameByPropertyPath('field1'));
        self::assertSame($field1, $config->findFieldByPath('field1'));
        self::assertSame($field1, $config->findFieldByPath('field1', true));

        self::assertSame($field2, $config->findField('field2'));
        self::assertNull($config->findField('field2', true));
        self::assertNull($config->findFieldNameByPropertyPath('field2'));
        self::assertSame($field2, $config->findFieldByPath('field2'));
        self::assertNull($config->findFieldByPath('field2', true));
        self::assertNull($config->findField('realField2'));
        self::assertSame($field2, $config->findField('realField2', true));
        self::assertSame('field2', $config->findFieldNameByPropertyPath('realField2'));
        self::assertNull($config->findFieldByPath('realField2'));
        self::assertSame($field2, $config->findFieldByPath('realField2', true));

        self::assertSame($field3, $config->findField('field3'));
        self::assertSame($field3, $config->findField('field3', true));
        self::assertSame('field3', $config->findFieldNameByPropertyPath('field3'));
        self::assertSame($field3, $config->findFieldByPath('field3'));
        self::assertSame($field3, $config->findFieldByPath('field3', true));

        self::assertSame($swapField, $config->findField('swapField'));
        self::assertSame($realSwapField, $config->findField('swapField', true));
        self::assertSame('realSwapField', $config->findFieldNameByPropertyPath('swapField'));
        self::assertSame($swapField, $config->findFieldByPath('swapField'));
        self::assertSame($realSwapField, $config->findFieldByPath('swapField', true));
        self::assertSame($realSwapField, $config->findField('realSwapField'));
        self::assertSame($swapField, $config->findField('realSwapField', true));
        self::assertSame('swapField', $config->findFieldNameByPropertyPath('realSwapField'));
        self::assertSame($realSwapField, $config->findFieldByPath('realSwapField'));
        self::assertSame($swapField, $config->findFieldByPath('realSwapField', true));

        self::assertSame($ignoredField, $config->findField('ignoredField'));
        self::assertNull($config->findField('ignoredField', true));
        self::assertNull($config->findField(ConfigUtil::IGNORE_PROPERTY_PATH));
        self::assertNull($config->findField(ConfigUtil::IGNORE_PROPERTY_PATH, true));
        self::assertSame('ignoredField', $config->findFieldNameByPropertyPath('ignoredField'));
        self::assertNull($config->findFieldNameByPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH));
        self::assertSame($ignoredField, $config->findFieldByPath('ignoredField'));
        self::assertNull($config->findFieldByPath('ignoredField', true));
        self::assertNull($config->findFieldByPath(ConfigUtil::IGNORE_PROPERTY_PATH));
        self::assertNull($config->findFieldByPath(ConfigUtil::IGNORE_PROPERTY_PATH, true));

        self::assertSame($replacedField, $config->findField('replacedField'));
        self::assertSame($realReplacedField, $config->findField('replacedField', true));
        self::assertSame('realReplacedField', $config->findFieldNameByPropertyPath('replacedField'));
        self::assertSame($replacedField, $config->findFieldByPath('replacedField'));
        self::assertSame($realReplacedField, $config->findFieldByPath('replacedField', true));

        self::assertSame($realReplacedField, $config->findField('realReplacedField'));
        self::assertNull($config->findField('realReplacedField', true));
        self::assertNull($config->findFieldNameByPropertyPath('realReplacedField'));
        self::assertSame($realReplacedField, $config->findFieldByPath('realReplacedField'));
        self::assertNull($config->findFieldByPath('realReplacedField', true));
    }

    public function testFindFieldByPathForChildFields()
    {
        $config = new EntityDefinitionConfig();

        $field1 = $config->addField('field1');
        $field11 = $field1->createAndSetTargetEntity()->addField('field11');
        $field11->setPropertyPath('realField11');
        $field111 = $field11->createAndSetTargetEntity()->addField('field111');

        self::assertSame($field111, $config->findFieldByPath('field1.field11.field111'));
        self::assertNull($config->findFieldByPath('field1.field11.field111', true));
        self::assertSame($field111, $config->findFieldByPath('field1.realField11.field111', true));

        self::assertSame($field111, $config->findFieldByPath(['field1', 'field11', 'field111']));
        self::assertNull($config->findFieldByPath(['field1', 'field11', 'field111'], true));
        self::assertSame($field111, $config->findFieldByPath(['field1', 'realField11', 'field111'], true));
    }

    public function testGetOrAddField()
    {
        $config = new EntityDefinitionConfig();

        $field = $config->getOrAddField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = $config->getOrAddField('field');
        self::assertSame($field, $field1);
    }

    public function testAddField()
    {
        $config = new EntityDefinitionConfig();

        $field = $config->addField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = new EntityDefinitionFieldConfig();
        $field1 = $config->addField('field', $field1);
        self::assertSame($field1, $config->getField('field'));
        self::assertNotSame($field, $field1);
    }

    public function testExclusionPolicy()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertTrue($config->isEmpty());

        $config->setExclusionPolicy('all');
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('all', $config->getExclusionPolicy());
        self::assertTrue($config->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExclusionPolicy('none');
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExcludeAll();
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('all', $config->getExclusionPolicy());
        self::assertTrue($config->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExcludeNone();
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExclusionPolicy(null);
        self::assertFalse($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertTrue($config->isEmpty());
    }

    public function testCollapsed()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->isCollapsed());

        $config->setCollapsed();
        self::assertTrue($config->isCollapsed());
        self::assertEquals(['collapse' => true], $config->toArray());

        $config->setCollapsed(false);
        self::assertFalse($config->isCollapsed());
        self::assertEquals([], $config->toArray());
    }

    public function testPageSize()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasPageSize());
        self::assertNull($config->getPageSize());

        $config->setPageSize(50);
        self::assertTrue($config->hasPageSize());
        self::assertEquals(50, $config->getPageSize());
        self::assertEquals(['page_size' => 50], $config->toArray());

        $config->setPageSize('100');
        self::assertTrue($config->hasPageSize());
        self::assertSame(100, $config->getPageSize());
        self::assertSame(['page_size' => 100], $config->toArray());

        $config->setPageSize(-1);
        self::assertTrue($config->hasPageSize());
        self::assertEquals(-1, $config->getPageSize());
        self::assertEquals(['page_size' => -1], $config->toArray());

        $config->setPageSize(null);
        self::assertFalse($config->hasPageSize());
        self::assertNull($config->getPageSize());
        self::assertEquals([], $config->toArray());
    }

    public function testSortingFlag()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDisableSorting());
        self::assertTrue($config->isSortingEnabled());

        $config->disableSorting();
        self::assertTrue($config->hasDisableSorting());
        self::assertFalse($config->isSortingEnabled());
        self::assertEquals(['disable_sorting' => true], $config->toArray());

        $config->enableSorting();
        self::assertTrue($config->hasDisableSorting());
        self::assertTrue($config->isSortingEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testInclusionFlag()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDisableInclusion());
        self::assertTrue($config->isInclusionEnabled());

        $config->disableInclusion();
        self::assertTrue($config->hasDisableInclusion());
        self::assertFalse($config->isInclusionEnabled());
        self::assertEquals(['disable_inclusion' => true], $config->toArray());

        $config->enableInclusion();
        self::assertTrue($config->hasDisableInclusion());
        self::assertTrue($config->isInclusionEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testFieldsetFlag()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDisableFieldset());
        self::assertTrue($config->isFieldsetEnabled());

        $config->disableFieldset();
        self::assertTrue($config->hasDisableFieldset());
        self::assertFalse($config->isFieldsetEnabled());
        self::assertEquals(['disable_fieldset' => true], $config->toArray());

        $config->enableFieldset();
        self::assertTrue($config->hasDisableFieldset());
        self::assertTrue($config->isFieldsetEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testMetaPropertiesFlag()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());

        $config->disableMetaProperties();
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertFalse($config->isMetaPropertiesEnabled());
        self::assertEquals(['disable_meta_properties' => true], $config->toArray());

        $config->enableMetaProperties();
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testIdentifierFieldNames()
    {
        $config = new EntityDefinitionConfig();
        self::assertEquals([], $config->getIdentifierFieldNames());

        $config->setIdentifierFieldNames(['id']);
        self::assertEquals(['id'], $config->getIdentifierFieldNames());
        self::assertEquals(['identifier_field_names' => ['id']], $config->toArray());

        $config->setIdentifierFieldNames([]);
        self::assertEquals([], $config->getIdentifierFieldNames());
        self::assertEquals([], $config->toArray());
    }

    public function testMaxResults()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasMaxResults());
        self::assertNull($config->getMaxResults());

        $config->setMaxResults(50);
        self::assertTrue($config->hasMaxResults());
        self::assertEquals(50, $config->getMaxResults());
        self::assertEquals(['max_results' => 50], $config->toArray());

        $config->setMaxResults('100');
        self::assertTrue($config->hasMaxResults());
        self::assertSame(100, $config->getMaxResults());
        self::assertSame(['max_results' => 100], $config->toArray());

        $config->setMaxResults(-1);
        self::assertTrue($config->hasMaxResults());
        self::assertEquals(-1, $config->getMaxResults());
        self::assertEquals(['max_results' => -1], $config->toArray());

        $config->setMaxResults(null);
        self::assertFalse($config->hasMaxResults());
        self::assertNull($config->getMaxResults());
        self::assertEquals([], $config->toArray());
    }

    public function testFormType()
    {
        $config = new EntityDefinitionConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions()
    {
        $config = new EntityDefinitionConfig();
        self::assertNull($config->getFormOptions());

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetFormOption()
    {
        $config = new EntityDefinitionConfig();

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

    public function testFormConstraints()
    {
        $config = new EntityDefinitionConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }

    public function testFormEventSubscribers()
    {
        $config = new EntityDefinitionConfig();
        self::assertNull($config->getFormEventSubscribers());

        $config->setFormEventSubscribers(['subscriber1']);
        self::assertEquals(['subscriber1'], $config->getFormEventSubscribers());
        self::assertEquals(['form_event_subscriber' => ['subscriber1']], $config->toArray());

        $config->setFormEventSubscribers([]);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetNullToFormEventSubscribers()
    {
        $config = new EntityDefinitionConfig();
        $config->setFormEventSubscribers(['subscriber1']);

        $config->setFormEventSubscribers(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    /**
     * @expectedException \TypeError
     */
    public function testSetInvalidValueToFormEventSubscribers()
    {
        $config = new EntityDefinitionConfig();
        $config->setFormEventSubscribers('subscriber1');
    }

    public function testHints()
    {
        $config = new EntityDefinitionConfig();
        self::assertEquals([], $config->getHints());

        $config->setHints(['hint1']);
        self::assertEquals(['hint1'], $config->getHints());
        self::assertEquals(['hints' => ['hint1']], $config->toArray());

        $config->setHints();
        self::assertEquals([], $config->getHints());
        self::assertEquals([], $config->toArray());

        $config->setHints(['hint1']);
        $config->setHints([]);
        self::assertEquals([], $config->getHints());
        self::assertEquals([], $config->toArray());
    }

    public function testIdentifierDescription()
    {
        $config = new EntityDefinitionConfig();
        self::assertFalse($config->hasIdentifierDescription());
        self::assertNull($config->getIdentifierDescription());

        $config->setIdentifierDescription('text');
        self::assertTrue($config->hasIdentifierDescription());
        self::assertEquals('text', $config->getIdentifierDescription());
        self::assertEquals(['identifier_description' => 'text'], $config->toArray());

        $config->setIdentifierDescription(null);
        self::assertFalse($config->hasIdentifierDescription());
        self::assertNull($config->getIdentifierDescription());
        self::assertEquals([], $config->toArray());

        $config->setIdentifierDescription('text');
        $config->setIdentifierDescription('');
        self::assertFalse($config->hasIdentifierDescription());
        self::assertNull($config->getIdentifierDescription());
        self::assertEquals([], $config->toArray());
    }
}
