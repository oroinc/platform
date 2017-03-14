<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class OroSimpleColorPickerField extends Element
{
    const ALLOWED_COLORS_MAPPING = [
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
        'Mercury' => '#E1E1E1'
    ];

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        self::assertArrayHasKey(
            $value,
            self::ALLOWED_COLORS_MAPPING,
            sprintf(
                'Color with name "%s" not found. Known names: "%s"',
                $value,
                implode(', ', array_keys(self::ALLOWED_COLORS_MAPPING))
            )
        );

        $colorHexCode = self::ALLOWED_COLORS_MAPPING[$value];
        $this->find('css', "[data-color='$colorHexCode']")->click();
    }
}
