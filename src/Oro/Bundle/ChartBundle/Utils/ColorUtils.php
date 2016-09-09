<?php

namespace Oro\Bundle\ChartBundle\Utils;

/**
 * Utility class for color manipulations.
 */
class ColorUtils
{
   /**
     * Insert number of shaded colors after each color in the input
     *
     * @param mixed $colors Array or comma separated string with six symbol hex colors
     * @param int $numberOfShades Number of shaded colors to insert
     * @param float $shadePercent Percents of shade to incrementally apply
     * @return mixed Return the same type as the $colors input
     */
    public static function insertShadeColors($colors, $numberOfShades, $shadePercent)
    {
        $numberOfShades = (int) $numberOfShades;
        $asString = false;
        $shades = [];

        if (!is_array($colors)) {
            $asString = true;
            $colors = explode(',', $colors);
        }

        foreach ($colors as $color) {
            $shades[] = $color;
            for ($i = 1; $i <= $numberOfShades; $i++) {
                $shades[] = static::shadeColor($color, $i*$shadePercent);
            }
        }

        if ($asString) {
            $shades = implode(',', $shades);
        }

        return $shades;
    }

    /**
     * Lighten a color by specified percents
     *
     * @param string $color Six symbol hex color
     * @param float $shadePercent Percents of shade to apply
     * @return string Six symbol hex color
     */
    public static function shadeColor($color, $shadePercent)
    {
        $color = ltrim($color, '#');
        $color = array_map('hexdec', str_split($color, 2));
        $rgb = array_values($color);

        $rgb = array_map(function ($v) use ($shadePercent) {
            return min(255, $v + $v * $shadePercent);
        }, $rgb);

        return vsprintf('#%02x%02x%02x', $rgb);
    }
}
