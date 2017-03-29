<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

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
}
