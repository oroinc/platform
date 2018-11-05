<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class SidebarConfigMenu extends Element
{
    public function openNestedMenu($path)
    {
        $this->collapseAll();

        // wait for links will collapsed
        $this->spin(function (SidebarConfigMenu $element) {
            $linksCount = count($element->findAll('css', 'li.jstree-node'));
            $collapsedLinksCount = count($element->findAll('css', 'li.jstree-closed'));

            return $linksCount === $collapsedLinksCount;
        });

        $items = explode('/', $path);
        $context = $this->find('css', 'ul.jstree-container-ul');
        self::assertNotNull($context, 'System configuration not found');
        $lastLink = array_pop($items);

        while ($item = trim(array_shift($items))) {
            $link = $context->findLink($item);
            self::assertNotNull($link, sprintf('Link "%s" was not found in configuration menu', $item));

            $link->click();

            $accordionBody = $link->getParent()->find('css', 'ul.jstree-children');

            $isExpanded = $this->spin(function () use ($accordionBody) {
                return $accordionBody !== null
                    && $accordionBody->isVisible()
                    && $accordionBody->getAttribute('style') === '';
            }, 5);

            self::assertTrue($isExpanded, sprintf('Menu "%s" is still collapsed', $item));
            $context = $accordionBody;
        }

        $context->clickLink($lastLink);
    }

    /**
     * @return NodeElement[]
     */
    public function getIntegrations()
    {
        $this->openNestedMenu('System Configuration/Integrations');

        return $this->findAll('css', '#integrations ul[role="group"] li[role="treeitem"] a');
    }

    public function expandAll()
    {
        $expandAllLink = $this->find('css', 'a[title="Expand All"]');
        self::assertNotNull($expandAllLink, 'Expand All link not found');
        $expandAllLink->click();
    }

    public function collapseAll()
    {
        $collapseAllLink = $this->find('css', 'a[title="Collapse All"]');
        self::assertNotNull($collapseAllLink, 'Collapse All link not found');
        $collapseAllLink->click();
    }
}
