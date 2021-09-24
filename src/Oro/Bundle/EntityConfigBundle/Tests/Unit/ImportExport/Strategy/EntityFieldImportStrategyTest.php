<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ConfigModelAwareConstraintInterface;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumValuesUnique;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityFieldImportStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatabaseHelper */
    protected $databaseHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityFieldImportStrategy */
    protected $strategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    protected $strategyHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldNameValidationHelper */
    protected $validationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextInterface */
    protected $context;

    /** @var ConstraintFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $constraintFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );

        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->databaseHelper = $this->createMock(DatabaseHelper::class);
        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $this->constraintFactory = $this->createMock(ConstraintFactory::class);

        $this->strategy = $this->createStrategy();

        $this->context = $this->createMock(ContextInterface::class);

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(FieldConfigModel::class);
        $this->strategy->setFieldTypeProvider($this->fieldTypeProvider);
        $this->strategy->setTranslator($this->translator);
        $this->strategy->setFieldValidationHelper($this->validationHelper);
        $this->strategy->setConstraintFactory($this->constraintFactory);
    }

    public function testProcessWrongType()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException::class);
        $field = new \stdClass();
        $this->strategy->process($field);
    }

    public function testProcess()
    {
        $entity = new FieldConfigModel('testFieldName', 'integer');
        $entity->setEntity(new EntityConfigModel(\stdClass::class));

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);
        $this->fieldTypeProvider->expects(static::any())
            ->method('getFieldProperties')
            ->willReturn([]);

        $this->strategyHelper->expects($this->never())
            ->method('addValidationErrors');

        $this->validationHelper->expects($this->once())
            ->method('registerField')
            ->with($entity);

        self::assertSame($entity, $this->strategy->process($entity));
    }

    public function testProcessWrongFieldType()
    {
        $entity = new FieldConfigModel('testFieldName', 'manyToOne');

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);

        $this->strategyHelper->expects($this->atLeastOnce())
            ->method('addValidationErrors');

        $this->validationHelper->expects($this->never())
            ->method('registerField');

        self::assertNull($this->strategy->process($entity));
    }

    public function testProcessEmptyFieldName()
    {
        $entity = new FieldConfigModel('', 'manyToOne');

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);

        $this->strategyHelper->expects($this->atLeastOnce())
            ->method('addValidationErrors')
            ->with(['oro.entity_config.import.message.invalid_field_type']);

        $this->validationHelper->expects($this->never())
            ->method('registerField');

        self::assertNull($this->strategy->process($entity));
    }

    public function testProcessValidationErrors()
    {
        $entityModel = new EntityConfigModel(\stdClass::class);
        $entity = new FieldConfigModel('testFieldName', 'integer');
        $entity->setEntity($entityModel);

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);
        $this->fieldTypeProvider->expects(static::any())
            ->method('getFieldProperties')
            ->willReturn([]);

        $this->strategyHelper->expects($this->once())
            ->method('validateEntity')
            ->with($entity)
            ->willReturn(['first error message', 'second error message']);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['first error message', 'second error message'], $this->context);

        $this->validationHelper->expects($this->once())
            ->method('findFieldConfig')
            ->willReturn(null);

        self::assertNull($this->strategy->process($entity));
    }

    public function testProcessValidationErrorsOfEntityFields()
    {
        $entityModel = new EntityConfigModel(\stdClass::class);
        $entity = new FieldConfigModel('testFieldName', 'integer');
        $entity->fromArray('enum', ['enum_options' => [['id' => null, 'label' => 'label']]]);
        $entity->setEntity($entityModel);

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);
        $this->fieldTypeProvider->expects($this->once())
            ->method('getFieldProperties')
            ->willReturn([
                'enum' => [
                    'enum_options' => [
                        'constraints' => [
                            [EnumValuesUnique::class => null],
                            [ConfigModelAwareConstraintInterface::class => null]
                        ]
                    ]
                ]
            ]);

        $this->constraintFactory->expects($this->once())
            ->method('parse')
            ->with([
                [EnumValuesUnique::class => null],
                [ConfigModelAwareConstraintInterface::class => ['configModel' => $entity]]
            ])
            ->willReturnArgument(0);

        $this->strategyHelper->expects($this->exactly(3))
            ->method('validateEntity')
            ->withConsecutive(
                [$entity],
                [EnumValue::createFromArray(['id' => null, 'label' => 'label'])],
                [
                    [
                        [
                            'id' => null,
                            'label' => 'label'
                        ]
                    ],
                    [
                        [EnumValuesUnique::class => null],
                        [ConfigModelAwareConstraintInterface::class => ['configModel' => $entity]]
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                ['first error message', 'second error message']
            );

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['enum.enum_options: first error message second error message'], $this->context);

        $this->validationHelper->expects($this->once())
            ->method('findFieldConfig')
            ->willReturn(null);

        self::assertNull($this->strategy->process($entity));
    }

    /**
     * @return EntityFieldImportStrategy
     */
    protected function createStrategy()
    {
        return new EntityFieldImportStrategy(
            new EventDispatcher(),
            $this->strategyHelper,
            $this->fieldHelper,
            $this->databaseHelper
        );
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
