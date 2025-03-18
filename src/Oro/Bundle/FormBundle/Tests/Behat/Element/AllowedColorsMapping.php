<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

trait AllowedColorsMapping
{
    protected $allowedColorsMapping = [
        'Cornflower Blue' => '#6D8DD4',
        'Melrose' => '#76AAC5',
        'Turquoise' => '#5C9496',
        'Riptide' => '#99B3AA',
        'Apple green' => '#',
        'Dandelion yellow' => '#C3B172',
        'Orange' => '#C98950',
        'Vivid Tangerine' => '#D28E87',
        'Alizarin Crimson' => '#A24A4D',
        'Mauve' => '#A285B8',
        'Mercury' => '#949CA1',
        'Aqua' => '#00FFFF',
        'Aquamarine' => '#7FFFD4',
        'Azure' => '#F0FFFF',
        'Beige' => '#F5F5DC',
        'Bisque' => '#FFE4C4',
        'Black' => '#000000',
        'Lightyellow' => '#FFFFE0',
        'Lime' => '#00FF00',
        'Limegreen' => '#32CD32',
        'Linen' => '#FAF0E6',
        'Magenta' => '#FF00FF',
        'Maroon' => '#800000',
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
