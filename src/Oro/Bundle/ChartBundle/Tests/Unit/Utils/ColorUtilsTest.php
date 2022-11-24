<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Utils;

use Oro\Bundle\ChartBundle\Utils\ColorUtils;

class ColorUtilsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider shadeColorProvider
     */
    public function testShadeColor(string $color, float $shade, string $expectedColor)
    {
        $this->assertEquals($expectedColor, ColorUtils::shadeColor($color, $shade));
    }

    public function shadeColorProvider(): array
    {
        return [
            'shade by 20%' => ['#acd39c', 0.2, '#cefdbb'],
            'shade by 70%' => ['#101010', 0.7, '#1b1b1b'],
            'without hash' => ['acd39c', 0.2, '#cefdbb'],
            'black does not change' => ['#000000', 0.1, '#000000'],
            'bytes does not overflow' => ['#00fffd', 0.1, '#00ffff'],
            'shade by 0% has no effect' => ['#acd39c', 0, '#acd39c'],
            'shade by 100% is white' => ['#acd39c', 1, '#ffffff'],
        ];
    }

    /**
     * @dataProvider insertShadeColorsProvider
     */
    public function testInsertShadeColors(array|string $colors, int $nbShades, array|string $expectedColors)
    {
        $shadeColors = ColorUtils::insertShadeColors($colors, $nbShades, 0.2);
        $this->assertEquals($expectedColors, $shadeColors);
    }

    public function insertShadeColorsProvider(): array
    {
        return [
            [
                ['#acd39c'],
                0,
                ['#acd39c'],
            ],
            [
                ['#acd39c'],
                1,
                ['#acd39c', '#cefdbb']
            ],
            [
                ['#acd39c', '#7fab90'],
                2,
                ['#acd39c', '#cefdbb', '#f0ffda', '#7fab90', '#98cdac', '#b1efc9'],
            ],
            [
                '#acd39c,#7fab90',
                2,
                '#acd39c,#cefdbb,#f0ffda,#7fab90,#98cdac,#b1efc9',
            ],
        ];
    }
}
