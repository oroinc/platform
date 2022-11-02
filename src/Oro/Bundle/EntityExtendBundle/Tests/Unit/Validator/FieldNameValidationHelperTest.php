<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator;

use Doctrine\Inflector\Rules\English\InflectorFactory;
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
    private const ENTITY_CLASS = 'Test\Entity';
    private const REMOVE_ERROR_MESSAGE = 'error message';

    /** @var ConfigProviderMock */
    private $extendConfigProvider;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FieldNameValidationHelper */
    private $validationHelper;

    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->validationHelper = new FieldNameValidationHelper(
            $this->extendConfigProvider,
            $this->eventDispatcher,
            new NewEntitiesHelper(),
            (new InflectorFactory())->build()
        );
    }

    /**
     * @dataProvider canFieldBeRestoredProvider
     */
    public function testCanFieldBeRestored(string $fieldName, bool $expectedResult): void
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $this->addFieldConfig($fieldName, 'int');
        $this->addFieldConfig('active_field', 'int');
        $this->addFieldConfig('active_hidden_field', 'int', [], true);
        $this->addFieldConfig('deleted_field', 'int', ['is_deleted' => true]);
        $this->addFieldConfig('to_be_deleted_field', 'int', ['state' => ExtendScope::STATE_DELETE]);

        self::assertEquals(
            $expectedResult,
            $this->validationHelper->canFieldBeRestored($field)
        );
    }

    public function canFieldBeRestoredProvider(): array
    {
        return [
            ['activeField', false],
            ['activeHiddenField', false],
            ['deletedField', true],
            ['toBeDeletedField', true],
        ];
    }

    public function testGetRemoveFieldValidationErrorsWithoutError(): void
    {
        $fieldConfigModel = new FieldConfigModel();
        $event = new ValidateBeforeRemoveFieldEvent($fieldConfigModel);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ValidateBeforeRemoveFieldEvent::NAME);

        $result = $this->validationHelper->getRemoveFieldValidationErrors($fieldConfigModel);

        self::assertEquals([], $result);
    }

    public function testGetRemoveFieldValidationErrorsWithError(): void
    {
        $fieldConfigModel = new FieldConfigModel();
        $validationEvent = new ValidateBeforeRemoveFieldEvent($fieldConfigModel);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($validationEvent, ValidateBeforeRemoveFieldEvent::NAME)
            ->willReturnCallback(function (ValidateBeforeRemoveFieldEvent $event) {
                $event->addValidationMessage(self::REMOVE_ERROR_MESSAGE);

                return $event;
            });

        $result = $this->validationHelper->getRemoveFieldValidationErrors($fieldConfigModel);

        self::assertEquals([self::REMOVE_ERROR_MESSAGE], $result);
    }

    /**
     * @dataProvider findExtendFieldConfigProvider
     */
    public function testFindExtendFieldConfig(string $fieldName, ?string $expectedFieldName): void
    {
        $this->addFieldConfig('testField1', 'int');
        $this->addFieldConfig('testHiddenField1', 'int', [], true);
        $this->addFieldConfig('test_field_2', 'int');
        $this->addFieldConfig('test_hidden_field_2', 'int', [], true);

        $expectedConfig = $expectedFieldName
            ? $this->extendConfigProvider->getConfig(self::ENTITY_CLASS, $expectedFieldName)
            : null;

        self::assertSame(
            $expectedConfig,
            $this->validationHelper->findFieldConfig(self::ENTITY_CLASS, $fieldName)
        );
    }

    public function findExtendFieldConfigProvider(): array
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
     */
    public function testGetSimilarExistingFieldData(
        string $newFieldName,
        string $existingFieldName,
        array $values,
        array $expectedResult
    ): void {
        $this->addFieldConfig($existingFieldName, 'string', $values);

        self::assertSame(
            $expectedResult,
            $this->validationHelper->getSimilarExistingFieldData(self::ENTITY_CLASS, $newFieldName)
        );
    }

    public function hasFieldNameConflictProvider(): array
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

    public function testRegisterFieldField(): void
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

    protected function addFieldConfig(
        string $fieldName,
        string $fieldType = null,
        array $values = [],
        bool $hidden = false
    ): void {
        $this->extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            $fieldName,
            $fieldType,
            $values,
            $hidden
        );
    }

    protected function getFieldConfig(string $fieldName, array $values = []): Config
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
