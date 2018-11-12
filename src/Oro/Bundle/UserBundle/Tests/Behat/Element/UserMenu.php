<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class UserMenu extends Element
{
    private $links = [];

    protected function init()
    {
        $this->links = $this->findAll('css', 'ul.dropdown-menu li a');
    }

    public function open()
    {
        $this->find('css', '#user-menu > [data-toggle="dropdown"]')->click();
    }

    /**
     * @param string $locator
     */
    public function clickLink($locator)
    {
        self::assertTrue(
            $this->hasLink($locator),
            sprintf('Can\'t find "%s" item in user menu', $locator)
        );

        $this->getLinkByTitle($locator)->click();
    }

    /**
     * @inheritdoc
     */
    public function hasLink($title)
    {
        return (bool) $this->getLinkByTitle($title);
    }

    /**
     * @param $title
     * @return NodeElement
     */
    private function getLinkByTitle($title)
    {
        /** @var NodeElement $link */
        foreach ($this->links as $link) {
            if (preg_match(sprintf('/%s/i', $title), $link->getText())) {
                return $link;
            }
        }

        return null;
    }
}
