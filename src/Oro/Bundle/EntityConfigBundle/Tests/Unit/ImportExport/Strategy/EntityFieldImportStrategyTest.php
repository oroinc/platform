<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Strategy;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;

class EntityFieldImportStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DatabaseHelper */
    protected $databaseHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityFieldImportStrategy */
    protected $strategy;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ImportStrategyHelper */
    protected $strategyHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fieldTypeProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldTypeProvider->expects(static::any())
            ->method('getSupportedFieldTypes')
            ->willReturn(['type1', 'type2']);

        $this->fieldTypeProvider->expects(static::any())
            ->method('getFieldProperties')
            ->willReturn([]);

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );

        /** @var FieldHelper $fieldHelper */
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
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

        $this->strategy = $this->createStrategy();

        /** @var ContextInterface $context */
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel');
        $this->strategy->setFieldTypeProvider($this->fieldTypeProvider);
        $this->strategy->setTranslator($this->translator);
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
        $factory = $this->getMock('Oro\Bundle\FormBundle\Validator\ConstraintFactory');
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
     * @param FieldConfigModel $field
     * @param bool $isExist
     * @param FieldConfigModel|null $expected
     *
     * @dataProvider processProvider
     */
    public function testProcess($field, $isExist, $expected)
    {
        $this->databaseHelper->expects(static::any())->method('findOneBy')->willReturn($isExist ? $field : null);

        static::assertEquals($expected, $this->strategy->process($field));
    }

    /**
     * @return array
     */
    public function processProvider()
    {
        $field = new FieldConfigModel('field_name', 'type1');

        $fieldWrongType = new FieldConfigModel('field_name', 'wrongType');

        $fieldSystem = new FieldConfigModel('field_name', 'type1');
        $fieldSystem->fromArray('extend', ['owner' => ExtendScope::OWNER_SYSTEM], []);

        return [
            'empty' => [
                'field' => new FieldConfigModel(),
                'isExist' => false,
                'expected' => null,
            ],
            'filled' => [
                'field' => $field,
                'isExist' => false,
                'expected' => $field,
            ],
            'wrong type' => [
                'field' => $fieldWrongType,
                'isExist' => false,
                'expected' => null,
            ],
            'system' => [
                'field' => $fieldSystem,
                'isExist' => true,
                'expected' => null,
            ],
        ];
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
