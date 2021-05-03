<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityFieldImportStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatabaseHelper */
    private $databaseHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
    private $fieldTypeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityFieldImportStrategy */
    private $strategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    private $fieldHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    private $strategyHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldNameValidationHelper */
    private $validationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextInterface */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->databaseHelper = $this->createMock(DatabaseHelper::class);
        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->fieldTypeProvider->expects(self::any())
            ->method('getFieldProperties')
            ->willReturn([]);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $this->strategy = $this->createStrategy();
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(FieldConfigModel::class);
        $this->strategy->setFieldTypeProvider($this->fieldTypeProvider);
        $this->strategy->setTranslator($this->translator);
        $this->strategy->setFieldValidationHelper($this->validationHelper);
    }

    public function testSetTranslator()
    {
        $strategy = $this->createStrategy();
        self::assertNull(ReflectionUtil::getPropertyValue($strategy, 'translator'));
        $strategy->setTranslator($this->translator);
        self::assertSame($this->translator, ReflectionUtil::getPropertyValue($strategy, 'translator'));
    }

    public function testSetConstraintFactory()
    {
        $factory = $this->createMock(ConstraintFactory::class);
        $strategy = $this->createStrategy();
        self::assertNull(ReflectionUtil::getPropertyValue($strategy, 'constraintFactory'));
        $strategy->setConstraintFactory($factory);
        self::assertSame($factory, ReflectionUtil::getPropertyValue($strategy, 'constraintFactory'));
    }

    public function testSetFieldTypeProvider()
    {
        $strategy = $this->createStrategy();
        self::assertNull(ReflectionUtil::getPropertyValue($strategy, 'fieldTypeProvider'));
        $strategy->setFieldTypeProvider($this->fieldTypeProvider);
        self::assertSame($this->fieldTypeProvider, ReflectionUtil::getPropertyValue($strategy, 'fieldTypeProvider'));
    }

    public function testProcessWrongType()
    {
        $this->expectException(InvalidArgumentException::class);
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

    /**
     * @return EntityFieldImportStrategy
     */
    private function createStrategy()
    {
        return new EntityFieldImportStrategy(
            new EventDispatcher(),
            $this->strategyHelper,
            $this->fieldHelper,
            $this->databaseHelper
        );
    }
}
