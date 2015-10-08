<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ConfigModelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Oro\Bundle\TestBundle\Entity\TestEntity';
    const TEST_MODULE = 'OroTestBundle';
    const TEST_ENTITY = 'TestEntity';

    /**
     * @dataProvider modelProvider
     */
    public function testBaseProperties(ConfigModel $model)
    {
        // test get/set mode
        $this->assertEquals(ConfigModel::MODE_DEFAULT, $model->getMode());
        $model->setMode(ConfigModel::MODE_READONLY);
        $this->assertEquals(ConfigModel::MODE_READONLY, $model->getMode());

        // test get/set created
        $this->assertNull($model->getCreated());
        $model->setCreated(new \DateTime('2013-01-01'));
        $this->assertEquals('2013-01-01', $model->getCreated()->format('Y-m-d'));

        // test get/set updated
        $this->assertNull($model->getUpdated());
        $model->setUpdated(new \DateTime('2013-01-01'));
        $this->assertEquals('2013-01-01', $model->getUpdated()->format('Y-m-d'));
    }

    public function testEntityConfigModel()
    {
        $className   = 'Test\TestClass';
        $entityModel = new EntityConfigModel($className);

        $this->assertEmpty($entityModel->getId());
        $this->assertEquals($className, $entityModel->getClassName());

        $className1 = 'Test\TestClass1';
        $entityModel->setClassName($className1);
        $this->assertEquals($className1, $entityModel->getClassName());
    }

    public function testFieldsCollectionOfEntityConfigModel()
    {
        $entityModel = new EntityConfigModel();
        $this->assertCount(0, $entityModel->getFields());

        $fieldModel1 = new FieldConfigModel('field1', 'string');
        $fieldModel2 = new FieldConfigModel('field2', 'integer');

        $entityModel->addField($fieldModel1);
        $entityModel->addField($fieldModel2);
        $fields = $entityModel->getFields();
        $this->assertCount(2, $fields);
        $this->assertSame($fieldModel1, $fields->first());
        $this->assertSame($fieldModel2, $fields->last());

        $this->assertSame($fieldModel1, $entityModel->getField('field1'));
        $this->assertSame($fieldModel2, $entityModel->getField('field2'));

        $fields = $entityModel->getFields(
            function (FieldConfigModel $model) {
                return $model->getFieldName() === 'field2';
            }
        );
        $this->assertCount(1, $fields);
        $this->assertSame($fieldModel2, $fields->first());

        $entityModel->setFields(new ArrayCollection([$fieldModel1]));
        $fields = $entityModel->getFields();
        $this->assertCount(1, $fields);
        $this->assertSame($fieldModel1, $fields->first());
    }

    public function testFieldConfigModel()
    {
        $fieldName   = 'testField';
        $fieldType   = 'integer';
        $entityModel = new EntityConfigModel('Test\TestClass');

        $fieldModel = new FieldConfigModel($fieldName, $fieldType);
        $fieldModel->setEntity($entityModel);

        $this->assertEmpty($fieldModel->getId());
        $this->assertEquals($fieldName, $fieldModel->getFieldName());
        $this->assertEquals($fieldType, $fieldModel->getType());
        $this->assertSame($entityModel, $fieldModel->getEntity());

        $fieldName1 = 'testField';
        $fieldType1 = 'integer';
        $fieldModel->setFieldName($fieldName1);
        $fieldModel->setType($fieldType1);
        $this->assertEquals($fieldName1, $fieldModel->getFieldName());
        $this->assertEquals($fieldType1, $fieldModel->getType());
    }

    /**
     * @dataProvider modelProvider
     */
    public function testFromArrayAndToArray(ConfigModel $model)
    {
        $values  = [
            'is_searchable' => true,
            'is_sortable'   => false,
            'doctrine'      => [
                'code' => 'test_001',
                'type' => 'string'
            ]
        ];
        $indexed = [
            'is_sortable' => true
        ];

        $model->fromArray('datagrid', $values, $indexed);
        $this->assertEquals($values, $model->toArray('datagrid'));

        $indexedValues = $model->getIndexedValues();
        $expectedCount = $model instanceof EntityConfigModel ? 3 : 1;
        $this->assertCount($expectedCount, $indexedValues);
        $indexedValues = $indexedValues->toArray();

        if ($model instanceof EntityConfigModel) {
            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('module_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_MODULE, $indexedValue->getValue());

            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('entity_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_ENTITY, $indexedValue->getValue());
        }

        /** @var ConfigModelIndexValue $indexedValue */
        $indexedValue = array_shift($indexedValues);
        $this->assertEquals('datagrid', $indexedValue->getScope());
        $this->assertEquals('is_sortable', $indexedValue->getCode());
        $this->assertEquals(0, $indexedValue->getValue());

        if ($model instanceof FieldConfigModel) {
            $this->assertNull($indexedValue->getEntity());
            $this->assertSame($model, $indexedValue->getField());
        } else {
            $this->assertSame($model, $indexedValue->getEntity());
            $this->assertNull($indexedValue->getField());
        }
    }

    /**
     * @dataProvider modelProvider
     */
    public function testFromArray(ConfigModel $model)
    {
        $values  = [
            'value1'   => 1,
            'value2'   => 2,
            'indexed1' => 3,
            'indexed2' => 4,
        ];
        $indexed = [
            'indexed1' => true,
            'indexed2' => true,
        ];

        $model->fromArray('test_scope', $values, $indexed);
        $this->assertEquals($values, $model->toArray('test_scope'));

        $indexedValues = $model->getIndexedValues();
        $expectedCount = $model instanceof EntityConfigModel ? 4 : 2;
        $this->assertCount($expectedCount, $indexedValues);
        $indexedValues = $indexedValues->toArray();

        /** @var ConfigModelIndexValue $indexedValue */
        if ($model instanceof EntityConfigModel) {
            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('module_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_MODULE, $indexedValue->getValue());

            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('entity_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_ENTITY, $indexedValue->getValue());
        }

        $indexedValue = array_shift($indexedValues);
        $this->assertEquals('test_scope', $indexedValue->getScope());
        $this->assertEquals('indexed1', $indexedValue->getCode());
        $this->assertEquals(3, $indexedValue->getValue());

        $indexedValue = array_shift($indexedValues);
        $this->assertEquals('test_scope', $indexedValue->getScope());
        $this->assertEquals('indexed2', $indexedValue->getCode());
        $this->assertEquals(4, $indexedValue->getValue());

        $values = [
            'value2'   => 1,
            'indexed2' => 2,
        ];
        $model->fromArray('test_scope', $values, $indexed);
        $this->assertEquals($values, $model->toArray('test_scope'));

        $indexedValues = $model->getIndexedValues();
        $expectedCount = $model instanceof EntityConfigModel ? 3 : 1;
        $this->assertCount($expectedCount, $indexedValues);
        $indexedValues = $indexedValues->toArray();

        if ($model instanceof EntityConfigModel) {
            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('module_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_MODULE, $indexedValue->getValue());

            $indexedValue = array_shift($indexedValues);
            $this->assertEquals('entity_config', $indexedValue->getScope());
            $this->assertEquals('entity_name', $indexedValue->getCode());
            $this->assertEquals(self::TEST_ENTITY, $indexedValue->getValue());
        }

        $indexedValue = array_shift($indexedValues);
        $this->assertEquals('test_scope', $indexedValue->getScope());
        $this->assertEquals('indexed2', $indexedValue->getCode());
        $this->assertEquals(2, $indexedValue->getValue());

        $values = [];
        $model->fromArray('test_scope', $values, $indexed);
        $this->assertEquals($values, $model->toArray('test_scope'));
    }

    public function modelProvider()
    {
        return [
            [new EntityConfigModel(self::TEST_CLASS)],
            [new FieldConfigModel()]
        ];
    }
}
