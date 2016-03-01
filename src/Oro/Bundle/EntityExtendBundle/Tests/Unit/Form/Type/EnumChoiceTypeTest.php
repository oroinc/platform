<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;

class EnumChoiceTypeTest extends AbstractEnumTypeTestCase
{
    /** @var EnumChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new EnumChoiceType($this->configManager, $this->doctrine);
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'translatable_entity',
            $this->type->getParent()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_enum_choice',
            $this->type->getName()
        );
    }

    public function testBuildForm()
    {
        $this->doTestBuildForm($this->type);
    }

    public function testPreSetDataForExistingEntity()
    {
        $this->doTestPreSetDataForExistingEntity($this->type);
    }

    public function testPreSetDataForNullEntity()
    {
        $this->doTestPreSetDataForNullEntity($this->type);
    }

    public function testPreSetDataForFormWithoutDataClass()
    {
        $this->doTestPreSetDataForFormWithoutDataClass($this->type);
    }

    public function testPreSetDataForNewEntityKeepExistingValue()
    {
        $this->doTestPreSetDataForNewEntityKeepExistingValue($this->type);
    }

    public function testPreSetDataForNewEntity()
    {
        $this->doTestPreSetDataForNewEntity($this->type);
    }

    public function testPreSetDataForNewEntityWithMultiEnum()
    {
        $this->doTestPreSetDataForNewEntityWithMultiEnum($this->type);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions($multiple, $expanded, $expectedEmptyValue, $expectedEmptyData)
    {
        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestSetDefaultOptions(
            $this->type,
            $resolver,
            'test_enum',
            $multiple,
            $expanded
        );

        $this->assertEquals(
            [
                'empty_value' => $expectedEmptyValue,
                'empty_data' => $expectedEmptyData
            ],
            $resolvedOptions
        );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage Either "class" or "enum_code" must option must be set.
     */
    public function testClassNormalizerOptionsException()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);
        $resolver->resolve([
            'enum_code' => null,
            'class' => null
        ]);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage must be a child of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue"
     */
    public function testClassNormalizerUnexpectedEnumException()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);
        $resolver->resolve([
            'enum_code' => 'unknown'
        ]);
    }

    public function setDefaultOptionsProvider()
    {
        return [
            [false, false, 'oro.form.choose_value', null],
            [false, true, null, null],
            [true, false, null, null],
            [true, true, null, null]
        ];
    }
}
