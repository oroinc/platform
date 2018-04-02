<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * The element represents the main menu.
 */
class MainMenu extends Element
{
    /** @var Element */
    protected $dropDown;

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->dropDown = $this;
    }

    /**
     * @param string $path
     * @return NodeElement
     */
    public function openAndClick($path)
    {
        $this->dropDown = $this;
        $items = explode('/', $path);
        $linkLocator = trim(array_pop($items));

        if ($this->hasClass('scroller') && !$this->getParent()->hasClass('minimized')) {
            $this->clickByMenuTree($path);
        } else {
            $this->moveByMenuTree($path);
        }

        $link = $this->findVisibleLink($linkLocator);
        $link->click();

        return $link;
    }

    /**
     * @inheritdoc
     */
    public function hasLink($path)
    {
        try {
            $items = explode('/', $path);
            $linkLocator = trim(array_pop($items));

            $this->moveByMenuTree($path);
            $this->findVisibleLink($linkLocator);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     */
    private function moveByMenuTree($path)
    {
        $items = explode('/', $path);
        array_pop($items);
        while ($item = array_shift($items)) {
            $link = $this->findVisibleLink($item);
            $link->mouseOver();
            $this->getDropDown($link);
        }
    }

    /**
     * @param string $path
     */
    private function clickByMenuTree($path)
    {
        $items = explode('/', $path);
        array_pop($items);
        while ($item = array_shift($items)) {
            $link = $this->findVisibleLink($item);
            if ($link->find('xpath', './span')->hasClass('collapsed')) {
                $link->click();
            }
        }
    }

    /**
     * @param string $title
     * @return NodeElement
     */
    protected function findVisibleLink($title)
    {
        $title = trim($title);

        /** @var NodeElement $link */
        $link = $this->dropDown->spin(function (NodeElement $element) use ($title) {
            $link = $element->findLink($title);

            if ($link && $link->isVisible()) {
                return $link;
            }

            return null;
        }, 5);

        self::assertNotNull($link, sprintf('Menu item "%s" not found', $title));

        return $link;
    }

    /**
     * @param NodeElement $link
     */
    protected function getDropDown($link)
    {
        $this->dropDown = $this->elementFactory->wrapElement(
            'MainMenuDropdown',
            $link->getParent()->find('css', '.dropdown-menu')
        );
    }
}
