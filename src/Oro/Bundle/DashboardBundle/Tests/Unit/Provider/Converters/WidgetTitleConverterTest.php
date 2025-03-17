<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetTitleConverter;
use PHPUnit\Framework\TestCase;

class WidgetTitleConverterTest extends TestCase
{
    private WidgetTitleConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new WidgetTitleConverter();
    }

    public function testGetConvertedValueWhenValueIsEmpty(): void
    {
        $widgetConfig = ['label' => 'widget label'];

        self::assertSame('widget label', $this->converter->getConvertedValue($widgetConfig, []));
    }

    public function testGetConvertedValueWhenUseDefaultTurnedOn(): void
    {
        $widgetConfig = ['label' => 'widget label'];
        $value = ['useDefault' => true, 'title' => 'test label'];

        self::assertEquals('widget label', $this->converter->getConvertedValue($widgetConfig, $value));
    }

    public function testGetConvertedValueWhenUseDefaultTurnedOff(): void
    {
        $widgetConfig = ['label' => 'widget label'];
        $value = ['useDefault' => false, 'title' => 'test label'];

        self::assertEquals('test label', $this->converter->getConvertedValue($widgetConfig, $value));
    }
}
