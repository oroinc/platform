<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetItemsChoiceTypeConverter;
use PHPUnit\Framework\TestCase;

class WidgetItemsChoiceTypeConverterTest extends TestCase
{
    private WidgetItemsChoiceTypeConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new WidgetItemsChoiceTypeConverter();
    }

    public function testGetConvertedValue(): void
    {
        $value = 'test';
        self::assertEquals($value, $this->converter->getConvertedValue([], $value));
        self::assertEquals($value, $this->converter->getFormValue([], $value));
    }

    public function testGetDefaultValue(): void
    {
        $config = [
            'converter_attributes' => [
                'default_selected' => 'all'
            ],
            'options' => [
                'choices' => [
                    ['first' => 1],
                    ['second' => 2]
                ]
            ]
        ];

        self::assertEquals([1, 2], $this->converter->getConvertedValue([], null, $config));
        self::assertEquals([1, 2], $this->converter->getFormValue($config, null));

        $config['converter_attributes']['default_selected'] = [1];

        self::assertEquals([1], $this->converter->getConvertedValue([], null, $config));
        self::assertEquals([1], $this->converter->getFormValue($config, null));
    }

    public function testGetViewValue(): void
    {
        $value = ['first', 'second'];
        self::assertEquals(implode(',', $value), $this->converter->getViewValue($value));
    }
}
