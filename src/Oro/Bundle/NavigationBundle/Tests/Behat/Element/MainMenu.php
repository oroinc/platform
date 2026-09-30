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
    private $dropDown;

    #[\Override]
    protected function init()
    {
        $this->dropDown = $this;
    }

    protected function getDropDown(NodeElement $link): Element
    {
        return $this->elementFactory->wrapElement(
            'MainMenuDropdown',
            $link->getParent()->find('css', '.dropdown-menu')
        );
    }

    public function openAndClick(string $path): NodeElement
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

    #[\Override]
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

    public function selectSideSubmenu(string $item): NodeElement
    {
        $link = $this->findVisibleLink($item);

        if (!$this->isTopLevelSideMenuItem($link)) {
            // findLink() returns the first match in the DOM. It can be a link in the side menu overlay that
            // a previous navigation left open, not the top-level item. Thus close the overlay and look again.
            if ($this->isSideMenuOverlayOpen()) {
                $this->elementFactory->createElement('SideMenuOverlayCloseButton')->click();
                $link = $this->findVisibleLink($item);
            }

            if (!$this->isTopLevelSideMenuItem($link)) {
                throw new \LogicException(sprintf('Cannot find submenu "%s" in the side menu', $item));
            }
        }

        // Do not click already opened menu
        if (!$this->isSideMenuOverlayOpen() || !$link->getParent()->hasClass('active')) {
            $link->click();
        }

        return $link;
    }

    /**
     * The sidebar JS component creates the overlay markup. Thus the overlay does not exist on a page that is not
     * fully initialized or that never opened the menu. That state means "not open".
     */
    private function isSideMenuOverlayOpen(): bool
    {
        try {
            $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');

            return $menuOverlay->isIsset() && $menuOverlay->hasClass('open');
        } catch (\Throwable) {
            return false;
        }
    }

    public function walkAllMenuItems(): \Generator
    {
        $topLevelItems = $this->findAll('css', 'span.title-level-1');
        foreach ($topLevelItems as $topLevelItem) {
            $topLevelPrefix = $this->selectSideSubmenu($topLevelItem->getText())->getText() . '/ ';
            $currentLevel = 1;
            $currentLevelPrefix = '';
            $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');
            foreach ($menuOverlay->findAll('css', 'ul.menu-level-1 > li span.title') as $menuTitle) {
                $menuLevel = $this->getMenuLevel($menuTitle);
                if ($menuLevel < $currentLevel) {
                    $lastDelimPos = mb_strrpos($currentLevelPrefix, '/ ', -3);
                    $currentLevelPrefix = false !== $lastDelimPos
                        ? mb_substr($currentLevelPrefix, 0, $lastDelimPos + 2)
                        : '';
                }
                $currentLevel = $menuLevel;
                if ($menuTitle->getParent()->hasClass('unclickable')) {
                    $currentLevelPrefix .= $menuTitle->getText() . '/ ';
                    continue;
                }

                yield $topLevelPrefix . $currentLevelPrefix . $menuTitle->getText();
            }
        }
    }

    private function moveByMenuTree(string $path): void
    {
        $items = explode('/', $path);
        array_pop($items);
        while ($item = array_shift($items)) {
            $link = $this->findVisibleLink($item);
            $link->mouseOver();
            $this->dropDown = $this->getDropDown($link);
        }
    }

    private function clickByMenuTree(string $path): void
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

    private function walkSideMenu(string $path): NodeElement
    {
        $items = explode('/', $path);
        $link = $this->selectSideSubmenu(array_shift($items));

        if (empty($items)) {
            return $link; // single item of first level menu was passed within path
        }

        $currentItem = trim(array_shift($items));
        $currentLevel = 1;
        $menuOverlay = $this->elementFactory->createElement('SideMenuOverlay');
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
                sprintf('ancestor::li[contains(normalize-space(@data-original-text),"%s")]', $currentItem)
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

    private function getMenuLevel(NodeElement $menuTitle): int
    {
        $class = $menuTitle->getAttribute('class');

        $matches = [];
        if (!preg_match('/title\-level\-(\d)/', $class, $matches)) {
            throw new \LogicException(sprintf('Cannot determine "%s" menu level', $menuTitle->getText()));
        }

        return (int)$matches[1];
    }

    /**
     * The side menu renders again while the overlay opens or closes, so the title span can go away between
     * the lookup and the class check. Thus an error causes a retry and does not fail the step.
     */
    private function isTopLevelSideMenuItem(NodeElement $link): bool
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $title = $link->find('xpath', './span');

                return null !== $title && $title->hasClass('title-level-1');
            } catch (\Throwable) {
                usleep(100000);
            }
        }

        return false;
    }

    private function findVisibleLink(string $title): NodeElement
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
