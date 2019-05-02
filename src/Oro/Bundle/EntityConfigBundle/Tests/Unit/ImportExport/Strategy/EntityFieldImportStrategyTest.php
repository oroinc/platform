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
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fieldTypeProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldTypeProvider->expects(static::any())
            ->method('getFieldProperties')
            ->willReturn([]);

        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );

        /** @var FieldHelper $fieldHelper */
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Helper\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ImportStrategyHelper $strategyHelper */
        $this->strategyHelper = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->databaseHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\DatabaseHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);

        $this->strategy = $this->createStrategy();

        $this->context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel');
        $this->strategy->setFieldTypeProvider($this->fieldTypeProvider);
        $this->strategy->setTranslator($this->translator);
        $this->strategy->setFieldValidationHelper($this->validationHelper);
    }

    public function testSetTranslator()
    {
        $strategy = $this->createStrategy();
        static::assertNull($this->getProperty($strategy, 'translator'));
        $strategy->setTranslator($this->translator);
        static::assertEquals($this->translator, $this->getProperty($strategy, 'translator'));
    }

    public function testSetConstraintFactory()
    {
        /** @var ConstraintFactory $factory */
        $factory = $this->createMock('Oro\Bundle\FormBundle\Validator\ConstraintFactory');
        $strategy = $this->createStrategy();
        static::assertNull($this->getProperty($strategy, 'constraintFactory'));
        $strategy->setConstraintFactory($factory);
        static::assertEquals($factory, $this->getProperty($strategy, 'constraintFactory'));
    }

    public function testSetFieldTypeProvider()
    {
        $strategy = $this->createStrategy();
        static::assertNull($this->getProperty($strategy, 'fieldTypeProvider'));
        $strategy->setFieldTypeProvider($this->fieldTypeProvider);
        static::assertEquals($this->fieldTypeProvider, $this->getProperty($strategy, 'fieldTypeProvider'));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     */
    public function testProcessWrongType()
    {
        $field = new \stdClass();
        $this->strategy->process($field);
    }

    /**
     */
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

    public function testProcessValidationErrors()
    {
        $entity = new FieldConfigModel('testFieldName', 'integer');

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
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }
}
