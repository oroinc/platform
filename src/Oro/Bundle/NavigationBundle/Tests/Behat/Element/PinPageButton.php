<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class PinPageButton extends Element
{
    protected const HIGHLIGHTED_CLASS = 'gold-icon';

    /**
     * @return bool
     */
    public function isHighlited()
    {
        return $this->hasClass(self::HIGHLIGHTED_CLASS);
    }
}
