<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class UserMenu extends Element
{
    public function open()
    {
        $this->find('css', 'i.icon-sort-down')->click();
    }

    /**
     * @param string $locator
     */
    public function clickLink($locator)
    {
        $links = $this->findAll('css', 'ul.dropdown-menu li a');

        /** @var NodeElement $link */
        foreach ($links as $link) {
            if (preg_match(sprintf('/%s/i', $locator), $link->getText())) {
                $link->click();

                return;
            }
        }

        self::fail(sprintf('Can\'t find "%s" item in user menu', $locator));
    }
}
