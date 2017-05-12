<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class OroSimpleColorPickerField extends Element
{
    use AllowedColorsMapping;

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $colorHexCode = $this->getHexByColorName($value);
        $this->find('css', "[data-color='$colorHexCode']")->click();
    }

    /**
     * Retrieve colors
     *
     * @return array
     */
    public function getAvailableColors()
    {
        $colorSpans = $this->findAll('css', 'span[role="button"]');
        $colors = [];

        self::assertNotEmpty($colorSpans, "Color blocks not found");

        /** @var NodeElement $element */
        foreach ($colorSpans as $element) {
            $color = $element->getAttribute('data-color');

            if (!empty($color)) {
                $colors[] = $element->getAttribute('data-color');
            }
        }

        // last element is custom value block, so we remove it
        array_pop($colors);

        return $colors;
    }
}
