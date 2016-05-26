<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class MainMenu extends Element
{
    public function follow($path)
    {
        $items = explode('->', $path);
        $that = $this;

        while ($item = array_shift($items)) {
            /** @var NodeElement $link */
            $link = $that->findLink(trim($item));
            $link->click();
            $that = $link->getParent();
        }
    }
}
