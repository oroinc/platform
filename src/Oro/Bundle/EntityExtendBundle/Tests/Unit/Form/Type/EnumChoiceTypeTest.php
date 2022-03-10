<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumChoiceTypeTest extends AbstractEnumTypeTestCase
{
    private EnumChoiceType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new EnumChoiceType($this->configManager, $this->doctrine);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(
            TranslatableEntityType::class,
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
     */
    public function testConfigureOptions($multiple, array $options, array $expectedOptions): void
    {
        $resolver = $this->getOptionsResolver();

        $resolvedOptions = $this->doTestConfigureOptions(
            $this->type,
            $resolver,
            'test_enum',
            $multiple,
            $options['expanded'],
            $options
        );

        $this->assertEquals($expectedOptions, $resolvedOptions);
    }

    public function testClassNormalizerOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Either "class" or "enum_code" must option must be set.');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code' => null,
            'class' => null,
        ]);
    }

    public function testClassNormalizerUnexpectedEnumException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('must be a child of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue"');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code' => 'unknown',
        ]);
    }

    public function configureOptionsProvider(): array
    {
        return [
            'not multiple, not expanded' => [
                'multiple' => false,
                'options' => ['expanded' => false],
                'expectedOptions' => [
                    'placeholder' => 'oro.form.choose_value',
                    'empty_data' => null,
                ],
            ],
            'not multiple, not expanded, not null "placeholder"' => [
                'multiple' => false,
                'options' => ['expanded' => false, 'placeholder' => false],
                'expectedOptions' => [
                    'placeholder' => false,
                    'empty_data' => null,
                ],
            ],
            'not multiple, expanded' => [
                'multiple' => true,
                'options' => ['expanded' => false],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => [],
                ],
            ],
            'multiple, not expanded' => [
                'multiple' => false,
                'options' => ['expanded' => true],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => null,
                ],
            ],
            'multiple, expanded' => [
                'multiple' => true,
                'options' => ['expanded' => true],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => [],
                ],
            ],
            'multiple, expanded, other options' => [
                'multiple' => true,
                'options' => [
                    'expanded' => true,
                    'placeholder' => 'test',
                    'empty_data' => '123',
                ],
                'expectedOptions' => [
                    'placeholder' => 'test',
                    'empty_data' => '123',
                ],
            ],
        ];
    }
}
