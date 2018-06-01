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

        if ($this->hasClass('scroller') && $this->getParent()->hasClass('minimized')) {
            $link = $this->walkSideMenu($path);
        } else {
            if ($this->hasClass('scroller') && !$this->getParent()->hasClass('minimized')) {
                $this->clickByMenuTree($path);
            } else {
                $this->moveByMenuTree($path);
            }

            $link = $this->findVisibleLink($linkLocator);
        }

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

            if ($this->hasClass('scroller') && $this->getParent()->hasClass('minimized')) {
                $this->walkSideMenu($path);
                $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');
                if ($menuOverlay->hasClass('open')) {
                    $menuOverlayCloseButton = $this->elementFactory->createElement('SideMenuOverlayCloseButton');
                    $menuOverlayCloseButton->click();
                }
            } else {
                if ($this->hasClass('scroller') && !$this->getParent()->hasClass('minimized')) {
                    $this->clickByMenuTree($path);
                } else {
                    $this->moveByMenuTree($path);
                }

                $this->findVisibleLink($linkLocator);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $item
     * @return NodeElement
     */
    public function selectSideSubmenu(string $item)
    {
        $link = $this->findVisibleLink($item);
        if (!$link->find('xpath', './span')->hasClass('title-level-1')) {
            throw new \LogicException(sprintf('Cannot find submenu "%s" in the side menu', $item));
        }

        $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');

        // Do not click already opened menu
        if (!$menuOverlay->hasClass('open') || !$link->getParent()->hasClass('active')) {
            $link->click();
        }

        return $link;
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
     * @param string $path
     * @return NodeElement
     */
    private function walkSideMenu(string $path)
    {
        $items = explode('/', $path);
        $link = $this->selectSideSubmenu(array_shift($items));

        if (empty($items)) {
            return $link; // single item of first level menu was passed within path
        }

        $currentItem = trim(array_shift($items));
        $currentLevel = 1;

        $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');

        /** @var NodeElement $menuTitle */
        foreach ($menuOverlay->findAll('css', 'ul.menu-level-1 > li span.title') as $menuTitle) {
            $menuLevel = $this->getMenuLevel($menuTitle);

            if ($menuLevel <= $currentLevel) {
                break; // it's needed to check only nested menu items
            }

            if ($menuLevel !== $currentLevel + 1) {
                continue; // it's needed to check only direct children
            }

            if (!$menuTitle->has(
                'xpath',
                sprintf('ancestor::li[normalize-space(@data-original-text)="%s"]', $currentItem)
            )) {
                continue;
            }

            if (empty($items)) {
                return $menuTitle->getParent();
            }

            $currentItem = trim(array_shift($items));
            $currentLevel = $menuLevel;
        }

        throw new \LogicException(sprintf('Menu "%s" was not found on the page', $path));
    }

    /**
     * @param NodeElement $menuItem
     * @return int
     */
    private function getMenuLevel(NodeElement $menuTitle)
    {
        $class = $menuTitle->getAttribute('class');

        $matches = [];
        if (!preg_match('/title-level-(\d)/', $class, $matches)) {
            throw new \LogicException(sprintf('Cannot determine "%s" menu level', $menuTitle->getText()));
        }

        return (int)$matches[1];
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
