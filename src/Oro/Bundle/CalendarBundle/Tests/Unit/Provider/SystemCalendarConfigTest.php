<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class SystemCalendarConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configProvider
     */
    public function testConfig(
        $enabledSystemCalendar,
        $expectedIsPublicCalendarEnabled,
        $expectedIsSystemCalendarEnabled
    ) {
        $config = new SystemCalendarConfig($enabledSystemCalendar);
        $this->assertSame($expectedIsPublicCalendarEnabled, $config->isPublicCalendarEnabled());
        $this->assertSame($expectedIsSystemCalendarEnabled, $config->isSystemCalendarEnabled());
    }

    public function configProvider()
    {
        return [
            [false, false, false],
            [true, true, true],
            ['system', true, false],
            ['organization', false, true],
        ];
    }
}
