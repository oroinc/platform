<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumSelectTypeTest extends AbstractEnumTypeTestCase
{
    private EnumSelectType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new EnumSelectType($this->configManager, $this->doctrine);
    }

    public function testGetParent(): void
    {
        self::assertEquals(
            Select2TranslatableEntityType::class,
            $this->type->getParent()
        );
    }

    public function testBuildForm(): void
    {
        $this->doTestBuildForm($this->type);
    }

    public function testPreSetDataForExistingEntity(): void
    {
        $this->doTestPreSetDataForExistingEntity($this->type);
    }

    public function testPreSetDataForNullEntity(): void
    {
        $this->doTestPreSetDataForNullEntity($this->type);
    }

    public function testPreSetDataForFormWithoutDataClass(): void
    {
        $this->doTestPreSetDataForFormWithoutDataClass($this->type);
    }

    public function testPreSetDataForNewEntityKeepExistingValue(): void
    {
        $this->doTestPreSetDataForNewEntityKeepExistingValue($this->type);
    }

    public function testPreSetDataForNewEntity(): void
    {
        $this->doTestPreSetDataForNewEntity($this->type);
    }

    public function testPreSetDataForNewEntityWithMultiEnum(): void
    {
        $this->doTestPreSetDataForNewEntityWithMultiEnum($this->type);
    }

    /**
     * @dataProvider configureOptionsProvider
     *
     * @param bool $multiple
     * @param bool $expanded
     * @param mixed $expectedEmptyValue
     * @param mixed $expectedEmptyData
     */
    public function testConfigureOptions(
        bool $multiple,
        bool $expanded,
        mixed $expectedEmptyValue,
        mixed $expectedEmptyData
    ): void {
        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestConfigureOptions(
            $this->type,
            $resolver,
            'test_enum',
            $multiple,
            $expanded
        );

        self::assertEquals(
            [
                'placeholder' => $expectedEmptyValue,
                'empty_data' => $expectedEmptyData,
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.form.choose_value',
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
    public function configureOptionsProvider(): array
    {
        return [
            [false, false, '', null],
            [false, true, null, null],
            [true, false, null, []],
            [true, true, null, []],
        ];
    }

    public function testConfigureOptionsWithOverrideConfigs(): void
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
                    'placeholder' => $newPlaceholder,
                ],
            ]
        );

        self::assertEquals(
            [
                'placeholder' => null,
                'empty_data' => '',
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => $newPlaceholder,
                ],
                'disabled_values' => [],
                'excluded_values' => [],
            ],
            $resolvedOptions
        );
    }
}
