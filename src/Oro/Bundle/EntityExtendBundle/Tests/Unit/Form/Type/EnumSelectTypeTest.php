<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;

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
            Select2TranslatableEntityType::class,
            $this->type->getParent()
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
     * @dataProvider configureOptionsProvider
     * @param boolean $multiple
     * @param boolean $expanded
     * @param mixed $expectedEmptyValue
     * @param mixed $expectedEmptyData
     */
    public function testConfigureOptions($multiple, $expanded, $expectedEmptyValue, $expectedEmptyData)
    {
        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestConfigureOptions(
            $this->type,
            $resolver,
            'test_enum',
            $multiple,
            $expanded
        );

        $this->assertEquals(
            [
                'placeholder' => $expectedEmptyValue,
                'empty_data'  => $expectedEmptyData,
                'configs'     => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.form.choose_value'
                ],
                'disabled_values' => [],
                'excluded_values' => [],
            ],
            $resolvedOptions
        );
    }

    /**
     * @return array
     */
    public function configureOptionsProvider()
    {
        return [
            [false, false, '', null],
            [false, true, null, null],
            [true, false, null, null],
            [true, true, null, null]
        ];
    }

    public function testConfigureOptionsWithOverrideConfigs()
    {
        $newPlaceholder = 'Test Placeholder';

        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestConfigureOptions(
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
                'placeholder' => null,
                'empty_data'  => '',
                'configs'     => [
                    'allowClear'  => true,
                    'placeholder' => $newPlaceholder
                ],
                'disabled_values' => [],
                'excluded_values' => [],
            ],
            $resolvedOptions
        );
    }
}
