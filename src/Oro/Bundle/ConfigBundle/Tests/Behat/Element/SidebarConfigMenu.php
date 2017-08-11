<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class SidebarConfigMenu extends Element
{
    public function openNestedMenu($path)
    {
        $collapseAllLink = $this->find('css', 'a[data-action="accordion:collapse-all"]');
        self::assertNotNull($collapseAllLink, 'Collapse All link not found');
        $collapseAllLink->click();

        // wait for links will collapsed
        $this->spin(function (SidebarConfigMenu $element) {
            $linksCount = count($element->findAll('css', 'a[data-toggle="collapse"]'));
            $collapsedLinksCount = count($element->findAll('css', 'a.collapsed'));

            return $linksCount === $collapsedLinksCount;
        });

        $items = explode('/', $path);
        $context = $this->find('css', 'ul.system-configuration-accordion');
        self::assertNotNull($context, 'System configuration not found');
        $lastLink = array_pop($items);

        while ($item = trim(array_shift($items))) {
            $link = $context->findLink($item);
            self::assertNotNull($link, sprintf('Link "%s" was not found in configuration menu', $item));

            $link->click();

            $accordionBody = $link->getParent()->getParent()->find('css', 'div.accordion-body');

            $isUnrolled = $this->spin(function () use ($accordionBody) {
                return false !== strpos($accordionBody->getAttribute('style'), 'height: auto');
            }, 5);

            self::assertTrue($isUnrolled, sprintf('Menu "%s" is still collapsed', $item));
            $context = $accordionBody;
        }

        $context->clickLink($lastLink);
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    public function getIntegrations()
    {
        return $this->findAll('css', '#config_tab_group_integrations li a');
    }
}
