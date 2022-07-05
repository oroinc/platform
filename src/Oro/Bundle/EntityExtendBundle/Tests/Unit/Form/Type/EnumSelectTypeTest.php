<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumSelectTypeTest extends AbstractEnumTypeTestCase
{
    /** @var EnumSelectType */
    protected $type;

    protected function setUp(): void
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
                'choice_translation_domain' => false,
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
                'choice_translation_domain' => false,
            ],
            $resolvedOptions
        );
    }

    /**
     * @dataProvider disabledValuesProvider
     * @param array $choices
     * @param array|callable $disabledValues
     */
    public function testBuildViewDisableChoices(array $choices, $disabledValues): void
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $priority = 0;
        $result = [];
        foreach ($choices as $key => $item) {
            $data = new TestEnumValue($item, (string)$key, ++$priority, false);
            $choiceView = new ChoiceView($data, $item, (string)$key);
            $view->vars['choices'][] = $choiceView;

            if ((is_array($disabledValues) && in_array((string)$key, $disabledValues))
                || (is_callable($disabledValues) && $disabledValues((string)$key))
            ) {
                $choiceView->attr = ['disabled' => 'disabled'];
            }
            $result[] = $choiceView;
        }

        $options = [
            'disabled_values' => $disabledValues,
            'excluded_values' => []
        ];

        $this->type->buildView($view, $form, $options);
        $returnedChoices = $view->vars['choices'];

        $this->assertSame($result, $returnedChoices);
    }

    /**
     * @return array
     */
    public function disabledValuesProvider(): array
    {
        return [
            [
                'choices' => [
                    '0.5' => '05',
                    '5' => '5'
                ],
                'disabledValues' => ['5'],
            ],
            [
                'choices' => [
                    'zero point five' => 'zerofive',
                    'five' => 'five'
                ],
                'disabledValues' => ['five'],
            ],
            [
                'choices' => [
                    '0.5' => '05',
                    '5' => '5'
                ],
                'disabledValues' => static function ($value) {
                    unset($value);
                    return false;
                },
            ]
        ];
    }
}
