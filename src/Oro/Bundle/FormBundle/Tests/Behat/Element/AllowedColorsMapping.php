<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

trait AllowedColorsMapping
{
    protected $allowedColorsMapping = [
        'Cornflower Blue' => '#5484ED',
        'Melrose' => '#A4BDFC',
        'Turquoise' => '#46D6DB',
        'Riptide' => '#7AE7BF',
        'Apple green' => '#51B749',
        'Dandelion yellow' => '#FBD75B',
        'Orange' => '#FFB878',
        'Vivid Tangerine' => '#FF887C',
        'Alizarin Crimson' => '#DC2127',
        'Mauve' => '#DBADFF',
        'Mercury' => '#E1E1E1',
        'Aqua'=>'#00FFFF',
        'Aquamarine'=>'#7FFFD4',
        'Azure'=>'#F0FFFF',
        'Beige'=>'#F5F5DC',
        'Bisque'=>'#FFE4C4',
        'Black'=>'#000000',
        'Lightyellow'=>'#FFFFE0',
        'Lime'=>'#00FF00',
        'Limegreen'=>'#32CD32',
        'Linen'=>'#FAF0E6',
        'Magenta'=>'#FF00FF',
        'Maroon'=>'#800000',
    ];

    private function assertColorExisting($color)
    {
        self::assertArrayHasKey(
            $color,
            $this->allowedColorsMapping,
            sprintf(
                'Color with name "%s" not found. Known names: "%s"',
                $color,
                implode(', ', array_keys($this->allowedColorsMapping))
            )
        );
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHexByColorName($name)
    {
        $this->assertColorExisting($name);

        return $this->allowedColorsMapping[$name];
    }
}
