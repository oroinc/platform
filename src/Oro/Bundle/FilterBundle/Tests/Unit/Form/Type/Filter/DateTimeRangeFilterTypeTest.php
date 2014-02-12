<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractDateTypeTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;

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
            ->setMethods(array('getTimezone'))
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue(date_default_timezone_get()));

        $types = array(
            new FilterType($translator),
            new DateRangeType($localeSettings),
            new DateTimeRangeType($localeSettings),
            new DateRangeFilterType($translator)
        );

        $this->formExtensions[] = new CustomFormExtension($types);

        parent::setUp();
        $this->type = new DateTimeRangeFilterType($translator);
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
        $this->assertEquals(DateTimeRangeFilterType::NAME, $this->type->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'field_type' => DateTimeRangeType::NAME,
                    'date_parts' => array(
                        AbstractDateFilterType::PART_VALUE   => 'oro.filter.form.label_date_part.value',
                        AbstractDateFilterType::PART_DOW     => 'oro.filter.form.label_date_part.dayofweek',
                        AbstractDateFilterType::PART_WEEK    => 'oro.filter.form.label_date_part.week',
                        AbstractDateFilterType::PART_DAY     => 'oro.filter.form.label_date_part.day',
                        AbstractDateFilterType::PART_MONTH   => 'oro.filter.form.label_date_part.month',
                        AbstractDateFilterType::PART_QUARTER => 'oro.filter.form.label_date_part.quarter',
                        AbstractDateFilterType::PART_DOY     => 'oro.filter.form.label_date_part.dayofyear',
                        AbstractDateFilterType::PART_YEAR    => 'oro.filter.form.label_date_part.year',
                    ),
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'empty' => array(
                'bindData' => array(),
                'formData' => array('type' => null, 'value' => array('start' => '', 'end' => '')),
                'viewData' => array(
                    'value'          => array('type' => null, 'value' => array('start' => '', 'end' => '')),
                    'widget_options' => array('firstDay' => 1)
                ),
                'customOptions' => array(
                    'widget_options' => array('firstDay' => 1)
                )
            ),
        );
    }
}
