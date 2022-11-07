<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FilterTypeTest extends AbstractTypeTestCase
{
    /** @var FilterType */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->type = new FilterType($translator);
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
                    'field_type' => TextType::class,
                    'field_options' => [],
                    'operator_choices' => [],
                    'operator_type' => ChoiceType::class,
                    'operator_options' => [],
                    'show_filter' => false,
                    'lazy' => false
                ],
                'requiredOptions' => [
                    'field_type',
                    'field_options',
                    'operator_choices',
                    'operator_type',
                    'operator_options',
                    'show_filter'
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider(): array
    {
        return [
            'empty data' => [
                'bindData' => [],
                'formData' => ['type' => null, 'value' => null],
                'viewData' => [
                    'value' => ['type' => '', 'value' => ''],
                ],
                'customOptions' => [
                    'operator_choices' => []
                ],
            ],
            'empty choice' => [
                'bindData' => ['type' => '1', 'value' => ''],
                'formData' => ['value' => null],
                'viewData' => [
                    'value' => ['type' => '1', 'value' => ''],
                ],
                'customOptions' => [
                    'operator_choices' => []
                ],
            ],
            'invalid choice' => [
                'bindData' => ['type' => '-1', 'value' => ''],
                'formData' => ['value' => null],
                'viewData' => [
                    'value' => ['type' => '-1', 'value' => ''],
                ],
                'customOptions' => [
                    'operator_choices' => [
                        'Choice 1' => 1,
                    ]
                ],
            ],
            'without choice' => [
                'bindData' => ['value' => 'text'],
                'formData' => ['type' => null, 'value' => 'text'],
                'viewData' => [
                    'value' => ['type' => '', 'value' => 'text'],
                ],
                'customOptions' => [
                    'operator_choices' => [
                        'Choice 1' => 1
                    ]
                ],
            ],
        ];
    }
}
