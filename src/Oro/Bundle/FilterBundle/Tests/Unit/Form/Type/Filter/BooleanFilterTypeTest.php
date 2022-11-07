<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;

class BooleanFilterTypeTest extends AbstractTypeTestCase
{
    /** @var BooleanFilterType */
    private $type;

    private array $booleanChoices = [
        'oro.filter.form.label_type_yes' => BooleanFilterType::TYPE_YES,
        'oro.filter.form.label_type_no' => BooleanFilterType::TYPE_NO,
    ];

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();

        $types = [
            new FilterType($translator),
            new ChoiceFilterType($translator)
        ];

        $this->type = new BooleanFilterType($translator);
        $this->formExtensions[] = new CustomFormExtension($types);
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
                    'field_options' => [
                        'choices' => $this->booleanChoices,
                    ],
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
                ]
            ],
            'predefined value choice' => [
                'bindData' => ['value' => BooleanFilterType::TYPE_YES],
                'formData' => ['type' => null, 'value' => BooleanFilterType::TYPE_YES],
                'viewData' => [
                    'value' => ['type' => null, 'value' => BooleanFilterType::TYPE_YES],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => $this->booleanChoices,
                    ],
                ]
            ],
            'invalid value choice' => [
                'bindData' => ['value' => 'incorrect_value'],
                'formData' => ['type' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 'incorrect_value'],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => $this->booleanChoices
                    ],
                ]
            ],
        ];
    }
}
