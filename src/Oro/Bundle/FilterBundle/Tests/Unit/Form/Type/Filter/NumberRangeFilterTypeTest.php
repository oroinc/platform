<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;

class NumberRangeFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var NumberRangeFilterType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->formExtensions[] = new CustomFormExtension([
            new FilterType($translator),
            new NumberFilterType($translator),
        ]);

        parent::setUp();

        $this->type = new NumberRangeFilterType($translator);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testGetName()
    {
        $this->assertEquals(NumberRangeFilterType::NAME, $this->type->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        $this->markTestSkipped('setDefaultOptions is deprecated.');
    }

    /**
     * @return OptionsResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockOptionsResolver()
    {
        return $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
    }

    /**
     * @dataProvider configureOptionsProvider
     * @param array $defaultOptions
     */
    public function testConfigureOptions(array $defaultOptions)
    {
        $resolver = $this->createMockOptionsResolver();

        if ($defaultOptions) {
            $resolver->expects($this->once())->method('setDefaults')->with($defaultOptions)->will($this->returnSelf());
        }

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * @return array
     */
    public function configureOptionsProvider()
    {
        return [
            [
                'defaultOptions' => [
                    'operator_choices'  => [
                        NumberRangeFilterType::TYPE_BETWEEN         => 'oro.filter.form.label_type_range_between',
                        NumberRangeFilterType::TYPE_NOT_BETWEEN     => 'oro.filter.form.label_type_range_not_between',
                        NumberRangeFilterType::TYPE_EQUAL           => 'oro.filter.form.label_type_range_equals',
                        NumberRangeFilterType::TYPE_NOT_EQUAL       => 'oro.filter.form.label_type_range_not_equals',
                        NumberRangeFilterType::TYPE_GREATER_THAN    => 'oro.filter.form.label_type_range_more_than',
                        NumberRangeFilterType::TYPE_LESS_THAN       => 'oro.filter.form.label_type_range_less_than',
                        NumberRangeFilterType::TYPE_GREATER_EQUAL   => 'oro.filter.form.label_type_range_more_equals',
                        NumberRangeFilterType::TYPE_LESS_EQUAL      => 'oro.filter.form.label_type_range_less_equals',
                        FilterUtility::TYPE_EMPTY     => 'oro.filter.form.label_type_empty',
                        FilterUtility::TYPE_NOT_EMPTY => 'oro.filter.form.label_type_not_empty',
                    ],
                ]
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return [
            'empty range' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => null,
                    'value_end' => null,
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => null,
                        'value_end' => null,
                    ],
                ],
            ],
            'empty end value' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '1',
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '1',
                    'value_end' => null,
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => '1',
                        'value_end' => null,
                    ],
                ],
            ],
            'empty start value' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value_end' => '20',
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => null,
                    'value_end' => '20',
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => null,
                        'value_end' => '20',
                    ],
                ],
            ],
            'between range' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => '10',
                        'value_end' => '20',
                    ],
                ],
            ],
            'not between range' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => '10',
                        'value_end' => '20',
                    ],
                ],
            ],
        ];
    }
}
