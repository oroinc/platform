<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Utils;

use Oro\Bundle\ChartBundle\Utils\ColorUtils;
use PHPUnit\Framework\TestCase;

class ColorUtilsTest extends TestCase
{
    /**
     * @dataProvider shadeColorProvider
     */
    public function testShadeColor(string $color, float $shade, string $expectedColor): void
    {
        $this->assertEquals($expectedColor, ColorUtils::shadeColor($color, $shade));
    }

    public function shadeColorProvider(): array
    {
        return [
            'shade by 20%' => ['#b5d8da', 0.2, '#d9ffff'],
            'shade by 70%' => ['#101010', 0.7, '#1b1b1b'],
            'without hash' => ['b5d8da', 0.2, '#d9ffff'],
            'black does not change' => ['#000000', 0.1, '#000000'],
            'bytes does not overflow' => ['#00fffd', 0.1, '#00ffff'],
            'shade by 0% has no effect' => ['#b5d8da', 0, '#b5d8da'],
            'shade by 100% is white' => ['#b5d8da', 1, '#ffffff'],
        ];
    }

    /**
     * @dataProvider insertShadeColorsProvider
     */
    public function testInsertShadeColors(array|string $colors, int $nbShades, array|string $expectedColors): void
    {
        $shadeColors = ColorUtils::insertShadeColors($colors, $nbShades, 0.2);
        $this->assertEquals($expectedColors, $shadeColors);
    }

    public function insertShadeColorsProvider(): array
    {
        return [
            [
                ['#b5d8da'],
                0,
                ['#b5d8da'],
            ],
            [
                ['#b5d8da'],
                1,
                ['#b5d8da', '#d9ffff']
            ],
            [
                ['#b5d8da', '#7fab90'],
                2,
                ['#b5d8da', '#d9ffff', '#fdffff', '#7fab90', '#98cdac', '#b1efc9'],
            ],
            [
                '#b5d8da,#7fab90',
                2,
                '#b5d8da,#d9ffff,#fdffff,#7fab90,#98cdac,#b1efc9',
            ],
        ];
    }
}
