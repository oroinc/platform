<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractDateTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DateTimeRangeFilterTypeTest extends AbstractDateTypeTestCase
{
    /**
     * @var DateTimeRangeFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(['getTimezone'])
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue(date_default_timezone_get()));

        $subscriber = $this->getMockSubscriber('Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber');
        $types = [
            new FilterType($translator),
            new DateRangeType($localeSettings),
            new DateTimeRangeType($localeSettings),
            new DateRangeFilterType($translator, new DateModifierProvider(), $subscriber)
        ];

        $this->type = new DateTimeRangeFilterType($translator, new DateModifierProvider(), $subscriber);
        $this->formExtensions[] = new CustomFormExtension($types);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => DateTimeRangeType::class,
                    'widget_options' => [
                        'showDatevariables' => true,
                        'showTime'          => true,
                        'showTimepicker'    => true,
                    ]
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return [
            'empty' => [
                'bindData'      => [],
                'formData'      => ['type' => null, 'value' => ['start' => '', 'end' => ''], 'part' => null],
                'viewData'      => [
                    'value'          => ['type' => null, 'value' => ['start' => '', 'end' => ''], 'part' => null],
                    'widget_options' => ['firstDay' => 1],
                ],
                'customOptions' => [
                    'widget_options' => ['firstDay' => 1],
                ]
            ],
        ];
    }

    public function testBuildView()
    {
        $form = $this->factory->create(get_class($this->type));
        $view = $form->createView();

        $this->assertEquals(
            [
                'showDatevariables' => true,
                'showTime' => true,
                'showTimepicker' => true
            ],
            $view->vars['widget_options']
        );
        $this->assertEquals(
            [
                DateModifierProvider::PART_VALUE => 'oro.filter.form.label_date_part.value',
                DateModifierProvider::PART_DOW => 'oro.filter.form.label_date_part.dayofweek',
                DateModifierProvider::PART_WEEK => 'oro.filter.form.label_date_part.week',
                DateModifierProvider::PART_DAY => 'oro.filter.form.label_date_part.day',
                DateModifierProvider::PART_MONTH => 'oro.filter.form.label_date_part.month',
                DateModifierProvider::PART_QUARTER => 'oro.filter.form.label_date_part.quarter',
                DateModifierProvider::PART_DOY => 'oro.filter.form.label_date_part.dayofyear',
                DateModifierProvider::PART_YEAR => 'oro.filter.form.label_date_part.year'
            ],
            $view->vars['date_parts']
        );
        $this->assertEquals(
            [
                'value' => [
                    DateModifierProvider::VAR_NOW => 'oro.filter.form.label_date_var.now',
                    DateModifierProvider::VAR_TODAY => 'oro.filter.form.label_date_var.today',
                    DateModifierProvider::VAR_SOW => 'oro.filter.form.label_date_var.sow',
                    DateModifierProvider::VAR_SOM => 'oro.filter.form.label_date_var.som',
                    DateModifierProvider::VAR_SOQ => 'oro.filter.form.label_date_var.soq',
                    DateModifierProvider::VAR_SOY => 'oro.filter.form.label_date_var.soy',
                    DateModifierProvider::VAR_THIS_MONTH_W_Y => 'oro.filter.form.label_date_var.this_month_w_y',
                    DateModifierProvider::VAR_THIS_DAY_W_Y => 'oro.filter.form.label_date_var.this_day_w_y'
                ],
                'dayofweek' => [
                    DateModifierProvider::VAR_THIS_DAY => 'oro.filter.form.label_date_var.this_day'
                ],
                'week' => [
                    DateModifierProvider::VAR_THIS_WEEK => 'oro.filter.form.label_date_var.this_week'
                ],
                'day' => [
                    DateModifierProvider::VAR_THIS_DAY => 'oro.filter.form.label_date_var.this_day'
                ],
                'month' => [
                    DateModifierProvider::VAR_THIS_MONTH => 'oro.filter.form.label_date_var.this_month',
                    DateModifierProvider::VAR_FMQ => 'oro.filter.form.label_date_var.this_fmq'
                ],
                'quarter' => [
                    DateModifierProvider::VAR_THIS_QUARTER => 'oro.filter.form.label_date_var.this_quarter'
                ],
                'dayofyear' => [
                    DateModifierProvider::VAR_THIS_DAY => 'oro.filter.form.label_date_var.this_day',
                    DateModifierProvider::VAR_FDQ => 'oro.filter.form.label_date_var.this_fdq'
                ],
                'year' => [
                    DateModifierProvider::VAR_THIS_YEAR => 'oro.filter.form.label_date_var.this_year'
                ]
            ],
            $view->vars['date_vars']
        );
        $this->assertEquals(
            [
                'between' => DateRangeFilterType::TYPE_BETWEEN,
                'notBetween' => DateRangeFilterType::TYPE_NOT_BETWEEN,
                'moreThan' => DateRangeFilterType::TYPE_MORE_THAN,
                'lessThan' => DateRangeFilterType::TYPE_LESS_THAN,
                'equal' => DateRangeFilterType::TYPE_EQUAL,
                'notEqual' => DateRangeFilterType::TYPE_NOT_EQUAL
            ],
            $view->vars['type_values']
        );
    }
}
