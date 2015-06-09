<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetItemsChoiceTypeConverter;

class WidgetItemsChoiceTypeConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetItemsChoiceTypeConverter */
    protected $converter;

    public function setUp()
    {
        $this->converter = new WidgetItemsChoiceTypeConverter();
    }

    public function testGetConvertedValue()
    {
        $value = 'test';
        $this->assertEquals($value, $this->converter->getConvertedValue([], $value));
        $this->assertEquals($value, $this->converter->getFormValue([], $value));
    }

    public function testGetDefaultValue()
    {
        $choises = [
            [1 => 'first'],
            [2 => 'second']
        ];

        $config = [
            'converter_attributes' => [
                'default_selected' => 'all'
            ],
            'options'              => [
                'choices' => $choises
            ]
        ];

        $this->assertEquals([1, 2], $this->converter->getConvertedValue([], null, $config));
        $this->assertEquals([1, 2], $this->converter->getFormValue($config, null));

        $config['converter_attributes']['default_selected'] = [1];

        $this->assertEquals([1], $this->converter->getConvertedValue([], null, $config));
        $this->assertEquals([1], $this->converter->getFormValue($config, null));
    }

    public function testGetViewValue()
    {
        $value = ['first', 'second'];
        $this->assertEquals(implode(',', $value), $this->converter->getViewValue($value));
    }
}
