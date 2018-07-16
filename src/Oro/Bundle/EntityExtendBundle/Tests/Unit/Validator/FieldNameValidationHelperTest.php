<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldNameValidationHelperTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    const REMOVE_ERROR_MESSAGE = 'error message';

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var FieldNameValidationHelper */
    protected $validationHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    protected function setUp()
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        $this->extendConfigProvider = new ConfigProviderMock(
            $configManager,
            'extend'
        );
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->validationHelper = new FieldNameValidationHelper(
            $this->extendConfigProvider,
            $this->eventDispatcher,
            new NewEntitiesHelper()
        );
    }

    /**
     * @dataProvider canFieldBeRestoredProvider
     *
     * @param string $fieldName
     * @param bool   $expectedResult
     */
    public function testCanFieldBeRestored($fieldName, $expectedResult)
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $this->addFieldConfig($fieldName, 'int');
        $this->addFieldConfig('active_field', 'int');
        $this->addFieldConfig('active_hidden_field', 'int', [], true);
        $this->addFieldConfig('deleted_field', 'int', ['is_deleted' => true]);
        $this->addFieldConfig('to_be_deleted_field', 'int', ['state' => ExtendScope::STATE_DELETE]);

        $this->assertEquals(
            $expectedResult,
            $this->validationHelper->canFieldBeRestored($field)
        );
    }

    /**
     * @return array
     */
    public function canFieldBeRestoredProvider()
    {
        return [
            ['activeField', false],
            ['activeHiddenField', false],
            ['deletedField', true],
            ['toBeDeletedField', true],
        ];
    }

    public function testGetRemoveFieldValidationErrorsWithoutError()
    {
        $fieldConfigModel = new FieldConfigModel();
        $event = new ValidateBeforeRemoveFieldEvent($fieldConfigModel);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ValidateBeforeRemoveFieldEvent::NAME, $event);

        $result = $this->validationHelper->getRemoveFieldValidationErrors($fieldConfigModel);

        $this->assertEquals([], $result);
    }

    public function testGetRemoveFieldValidationErrorsWithError()
    {
        $fieldConfigModel = new FieldConfigModel();
        $validationEvent = new ValidateBeforeRemoveFieldEvent($fieldConfigModel);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ValidateBeforeRemoveFieldEvent::NAME, $validationEvent)
            ->willReturnCallback(
                function ($eventName, ValidateBeforeRemoveFieldEvent $event) {
                    $event->addValidationMessage(self::REMOVE_ERROR_MESSAGE);
                }
            );

        $result = $this->validationHelper->getRemoveFieldValidationErrors($fieldConfigModel);

        $this->assertEquals([self::REMOVE_ERROR_MESSAGE], $result);
    }

    /**
     * @dataProvider findExtendFieldConfigProvider
     *
     * @param string $fieldName
     * @param bool   $expectedFieldName
     */
    public function testFindExtendFieldConfig($fieldName, $expectedFieldName)
    {
        $this->addFieldConfig('testField1', 'int');
        $this->addFieldConfig('testHiddenField1', 'int', [], true);
        $this->addFieldConfig('test_field_2', 'int');
        $this->addFieldConfig('test_hidden_field_2', 'int', [], true);

        $expectedConfig = $expectedFieldName
            ? $this->extendConfigProvider->getConfig(self::ENTITY_CLASS, $expectedFieldName)
            : null;

        $this->assertSame(
            $expectedConfig,
            $this->validationHelper->findExtendFieldConfig(self::ENTITY_CLASS, $fieldName)
        );
    }

    /**
     * @return array
     */
    public function findExtendFieldConfigProvider()
    {
        return [
            ['unknownField', null],
            ['testField1', 'testField1'],
            ['testHiddenField1', 'testHiddenField1'],
            ['testfield1', 'testField1'],
            ['testhiddenfield1', 'testHiddenField1'],
            ['test_field1', 'testField1'],
            ['test_hidden_field1', 'testHiddenField1'],
            ['testField2', 'test_field_2'],
            ['testHiddenField2', 'test_hidden_field_2'],
        ];
    }

    /**
     * @dataProvider hasFieldNameConflictProvider
     *
     * @param string $newFieldName
     * @param string $existingFieldName
     * @param array $values
     * @param bool $expectedResult
     */
    public function testGetSimilarExistingFieldData($newFieldName, $existingFieldName, array $values, $expectedResult)
    {
        $this->addFieldConfig($existingFieldName, 'string', $values);

        $this->assertSame(
            $expectedResult,
            $this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, $newFieldName)
        );
    }

    /**
     * @return array
     */
    public function hasFieldNameConflictProvider()
    {
        return [
            ['testField', 'testField', [], ['testField', 'string']],
            ['test_field', 'testField', [], ['testField', 'string']],
            ['testField', 'test_field', [], ['test_field', 'string']],
            ['TestField', 'testField', [], ['testField', 'string']],
            ['testField', 'anotherField', [], []],
            ['testField', 'testField', ['is_deleted' => true], ['testField', 'string']],
            ['test_field', 'testField', ['is_deleted' => true], []],
            ['testField', 'test_field', ['is_deleted' => true], []],
            ['testField', 'testField', ['state' => ExtendScope::STATE_DELETE], ['testField', 'string']],
            ['test_field', 'testField', ['state' => ExtendScope::STATE_DELETE], []],
            ['testField', 'test_field', ['state' => ExtendScope::STATE_DELETE], []],
        ];
    }

    public function testRegisterFieldField()
    {
        $field1 = new FieldConfigModel('testField1', 'string');
        $field2 = new FieldConfigModel('testField2', 'string');

        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $entity->addField($field1);
        $entity->addField($field2);

        $this->validationHelper->registerField($field1);
        $this->validationHelper->registerField($field2);

        self::assertNotEmpty($this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, 'testField1'));
        self::assertNotEmpty($this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, 'test_field_1'));
        self::assertNotEmpty($this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, 'TEST_FIELD_1'));
        self::assertNotEmpty($this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, 'testField2'));
        self::assertNotEmpty($this->validationHelper
            ->getSimilarExistingFieldData(self::ENTITY_CLASS, 'testField1'));
        self::assertNotEmpty($this->validationHelper
            ->getSimilarExistingFieldData(self::ENTITY_CLASS, 'testField2'));

        self::assertEmpty($this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, 'testField3'));
    }

    /**
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     * @param bool   $hidden
     */
    protected function addFieldConfig($fieldName, $fieldType = null, array $values = [], $hidden = false)
    {
        $this->extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            $fieldName,
            $fieldType,
            $values,
            $hidden
        );
    }

    /**
     * @param string $fieldName
     * @param array  $values
     *
     * @return Config
     */
    protected function getFieldConfig($fieldName, array $values = [])
    {
        $fieldConfigId = new FieldConfigId(
            'extend',
            self::ENTITY_CLASS,
            $fieldName,
            'int'
        );
        $fieldConfig   = new Config($fieldConfigId);
        $fieldConfig->setValues($values);

        return $fieldConfig;
    }
}
