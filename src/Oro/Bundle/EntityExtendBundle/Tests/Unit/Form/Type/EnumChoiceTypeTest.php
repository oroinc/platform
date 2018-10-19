<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;

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
            TranslatableEntityType::class,
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
     *
     * @param array $options
     * @param array $expectedOptions
     */
    public function testConfigureOptions($multiple, array $options, array $expectedOptions)
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

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage Either "class" or "enum_code" must option must be set.
     */
    public function testClassNormalizerOptionsException()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
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
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code' => 'unknown'
        ]);
    }

    /**
     * @return array
     */
    public function configureOptionsProvider()
    {
        return [
            'not multiple, not expanded' => [
                'multiple' => false,
                'options' => ['expanded' => false],
                'expectedOptions' => [
                    'placeholder' => 'oro.form.choose_value',
                    'empty_data' => null,
                ]
            ],
            'not multiple, not expanded, not null "placeholder"' => [
                'multiple' => false,
                'options' => ['expanded' => false, 'placeholder' => false],
                'expectedOptions' => [
                    'placeholder' => false,
                    'empty_data' => null,
                ]
            ],
            'not multiple, expanded' => [
                'multiple' => true,
                'options' => ['expanded' => false],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => null,
                ]
            ],
            'multiple, not expanded' => [
                'multiple' => false,
                'options' => ['expanded' => true],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => null,
                ]
            ],
            'multiple, expanded' => [
                'multiple' => true,
                'options' => ['expanded' => true],
                'expectedOptions' => [
                    'placeholder' => null,
                    'empty_data' => null,
                ]
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
                ]
            ],
        ];
    }
}
