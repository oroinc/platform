<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractDateTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DateRangeFilterTypeTest extends AbstractDateTypeTestCase
{
    /**
     * @var DateRangeFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();

        $subscriber = $this->getMockSubscriber('Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber');

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getTimezone'))
            ->getMock();

        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('UTC'));

        $types = array(
            new DateRangeType($localeSettings),
            new FilterType($translator)
        );

        $this->type = new DateRangeFilterType($translator, new DateModifierProvider(), $subscriber);
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
        return array(
            array(
                'defaultOptions' => array(
                    'field_type' => DateRangeType::class,
                    'widget_options' => array(
                        'showDatevariables' => true,
                        'showTime'          => false,
                        'showTimepicker'    => false,
                    ),
                    'operator_choices' => array(
                        'oro.filter.form.label_date_type_between' => DateRangeFilterType::TYPE_BETWEEN,
                        'oro.filter.form.label_date_type_not_between' => DateRangeFilterType::TYPE_NOT_BETWEEN,
                        'oro.filter.form.label_date_type_more_than' => DateRangeFilterType::TYPE_MORE_THAN ,
                        'oro.filter.form.label_date_type_less_than' => DateRangeFilterType::TYPE_LESS_THAN,
                        'oro.filter.form.label_date_type_equals' => DateRangeFilterType::TYPE_EQUAL,
                        'oro.filter.form.label_date_type_not_equals' => DateRangeFilterType::TYPE_NOT_EQUAL
                    ),
                    'type_values' => array(
                        'between'    => DateRangeFilterType::TYPE_BETWEEN,
                        'notBetween' => DateRangeFilterType::TYPE_NOT_BETWEEN,
                        'moreThan'   => DateRangeFilterType::TYPE_MORE_THAN,
                        'lessThan'   => DateRangeFilterType::TYPE_LESS_THAN,
                        'equal'      => DateRangeFilterType::TYPE_EQUAL,
                        'notEqual'   => DateRangeFilterType::TYPE_NOT_EQUAL
                    )
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return [
            'empty' => [
                'bindData'      => [],
                'formData'      => ['type' => null, 'value' => array('start' => '', 'end' => ''), 'part' => null],
                'viewData'      => [
                    'value'          => [
                        'type'  => null,
                        'value' => array('start' => '', 'end' => ''),
                        'part'  => null
                    ],
                    'widget_options' => ['firstDay' => 1],
                ],
                'customOptions' => [
                    'widget_options' => ['firstDay' => 1]
                ]
            ],
        ];
    }
}
