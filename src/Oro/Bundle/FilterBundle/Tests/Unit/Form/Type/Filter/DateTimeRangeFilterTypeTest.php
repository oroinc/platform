<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
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

        $subscriber = $this->getMockSubscriber('Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber');
        $types = array(
            new FilterType($translator),
            new DateRangeType(),
            new DateTimeRangeType($localeSettings),
            new DateRangeFilterType($translator, new DateModifierProvider(), $subscriber)
        );

        $this->formExtensions[] = new CustomFormExtension($types);

        parent::setUp();
        $this->type = new DateTimeRangeFilterType($translator, new DateModifierProvider(), $subscriber);
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
        return [
            [
                'defaultOptions' => [
                    'field_type' => DateTimeRangeType::NAME,
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
                'formData'      => ['type' => null, 'value' => array('start' => '', 'end' => ''), 'part' => null],
                'viewData'      => [
                    'value'          => ['type' => null, 'value' => array('start' => '', 'end' => ''), 'part' => null],
                    'widget_options' => ['firstDay' => 1],
                ],
                'customOptions' => [
                    'widget_options' => ['firstDay' => 1],
                ]
            ],
        ];
    }
}
