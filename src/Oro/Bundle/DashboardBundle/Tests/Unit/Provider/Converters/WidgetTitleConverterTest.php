<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetTitleConverter;

class WidgetTitleConverterTest extends \PHPUnit\Framework\TestCase
{
    protected $converter;

    public function testGetConvertedValue()
    {
        $converter = new WidgetTitleConverter();
        $label = 'widget label';
        $config['label'] = $label;

        $this->assertSame($label, $converter->getConvertedValue($config, []));

        $value['useDefault'] = false;
        $value['title'] = 'test label';

        $this->assertEquals('test label', $converter->getConvertedValue($config, $value));
    }
}
