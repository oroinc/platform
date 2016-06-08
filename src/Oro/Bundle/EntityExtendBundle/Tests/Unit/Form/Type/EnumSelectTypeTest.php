<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;

class EnumSelectTypeTest extends AbstractEnumTypeTestCase
{
    /** @var EnumSelectType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new EnumSelectType($this->configManager, $this->doctrine);
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'genemu_jqueryselect2_translatable_entity',
            $this->type->getParent()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_enum_select',
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
                'empty_data'  => $expectedEmptyData,
                'configs'     => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.form.choose_value'
                ],
                'disabled_values' => []
            ],
            $resolvedOptions
        );
    }

    public function setDefaultOptionsProvider()
    {
        return [
            [false, false, '', null],
            [false, true, null, null],
            [true, false, null, null],
            [true, true, null, null]
        ];
    }

    public function testSetDefaultOptionsWithOverrideConfigs()
    {
        $newPlaceholder = 'Test Placeholder';

        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestSetDefaultOptions(
            $this->type,
            $resolver,
            'test_enum',
            false,
            false,
            [
                'configs' => [
                    'placeholder' => $newPlaceholder
                ]
            ]
        );

        $this->assertEquals(
            [
                'empty_value' => null,
                'empty_data'  => '',
                'configs'     => [
                    'allowClear'  => true,
                    'placeholder' => $newPlaceholder
                ],
                'disabled_values' => []
            ],
            $resolvedOptions
        );
    }
}
