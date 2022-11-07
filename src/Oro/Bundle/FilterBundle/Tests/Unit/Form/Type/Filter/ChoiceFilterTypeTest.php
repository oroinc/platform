<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChoiceFilterTypeTest extends AbstractTypeTestCase
{
    /** @var ChoiceFilterType */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->type = new ChoiceFilterType($translator);
        $this->formExtensions[] = new CustomFormExtension([new FilterType($translator)]);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => ChoiceType::class,
                    'field_options' => [],
                    'operator_choices' => [
                        'oro.filter.form.label_type_contains' => ChoiceFilterType::TYPE_CONTAINS,
                        'oro.filter.form.label_type_not_contains' => ChoiceFilterType::TYPE_NOT_CONTAINS,
                    ],
                    'populate_default' => false,
                    'default_value' => null,
                    'null_value' => null,
                    'class' => null,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider(): array
    {
        return [
            'empty' => [
                'bindData' => [],
                'formData' => ['type' => null, 'value' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => null],
                ],
            ],
            'predefined value choice' => [
                'bindData' => ['value' => 1],
                'formData' => ['type' => null, 'value' => 1],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 1],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => ['One' => 1, 'Two' => 2],
                    ],
                ],
            ],
            'invalid value choice' => [
                'bindData' => ['value' => 3],
                'formData' => ['type' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 3],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => ['One' => 1],
                    ],
                ],
            ],
            'multiple choices' => [
                'bindData' => ['value' => [1, 2]],
                'formData' => ['type' => null, 'value' => [1, 2]],
                'viewData' => [
                    'value' => ['type' => null, 'value' => [1, 2]],
                ],
                'customOptions' => [
                    'field_options' => [
                        'multiple' => true,
                        'choices' => ['One' => 1, 'Two' => 2, 'Three' => 3],
                    ],
                ],
            ],
            'invalid multiple choices' => [
                'bindData' => ['value' => [4, 5]],
                'formData' => ['type' => null, 'value' => []],
                'viewData' => [
                    'value' => ['type' => null, 'value' => []],
                ],
                'customOptions' => [
                    'field_options' => [
                        'multiple' => true,
                        'choices' => ['One' => 1, 'Two' => 2, 'Three' => 3],
                    ],
                ],
            ],
        ];
    }
}
