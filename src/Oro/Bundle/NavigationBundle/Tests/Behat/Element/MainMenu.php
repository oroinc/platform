<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class MainMenu extends Element
{
    /**
     * @param string $path
     * @throws ElementNotFoundException
     * @return NodeElement
     */
    public function openAndClick($path)
    {
        $items = explode('/', $path);
        $linkLocator = trim(array_pop($items));
        $that = $this;

        while ($item = array_shift($items)) {
            /** @var NodeElement $link */
            $link = $this->findVisibleLink($that, trim($item));
            $link->mouseOver();
            $that = $this->elementFactory->wrapElement(
                'MainMenuDropdown',
                $link->getParent()->find('css', '.dropdown-menu')
            );
        }

        $link = $this->findVisibleLink($that, trim($linkLocator));
        $link->click();

        return $link;
    }

    /**
     * @param Element $element
     * @param $item
     * @return NodeElement
     */
    protected function findVisibleLink(Element $element, $item)
    {
        /** @var NodeElement $link */
        $link = $element->spin(function (NodeElement $element) use ($item) {
            $link = $element->findLink(trim($item));

            if ($link && $link->isVisible()) {
                return $link;
            }

            return null;
        }, 5);

        self::assertNotNull($link, sprintf('Menu item "%s" not found', $item));

        return $link;
    }
}
