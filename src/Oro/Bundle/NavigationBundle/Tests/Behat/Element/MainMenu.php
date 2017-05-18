<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class MainMenu extends Element
{
    /** @var Element */
    private $dropDown = null;

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->dropDown = $this;
    }

    /**
     * @param string $path
     * @throws ElementNotFoundException
     * @return NodeElement
     */
    public function openAndClick($path)
    {
        $this->dropDown = $this;
        $items = explode('/', $path);
        $linkLocator = trim(array_pop($items));

        $this->moveByMenuTree($path);

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

            return null !== $this->findVisibleLink($linkLocator);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function moveByMenuTree($path)
    {
        $items = explode('/', $path);
        array_pop($items);

        while ($item = array_shift($items)) {
            /** @var NodeElement $link */
            $link = $this->findVisibleLink($item);
            $link->mouseOver();
            $this->dropDown = $this->elementFactory->wrapElement(
                'MainMenuDropdown',
                $link->getParent()->find('css', '.dropdown-menu')
            );
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
}
