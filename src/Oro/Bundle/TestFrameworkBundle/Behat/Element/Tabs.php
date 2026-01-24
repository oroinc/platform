<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;

/**
 * Represents a tabbed interface element in Behat tests.
 *
 * This element handles switching between tabs, checking tab existence, and retrieving
 * the currently active tab content. It uses CSS selectors to locate tab headers and content areas.
 */
class Tabs extends Element
{
    const SELECTOR_TAB_HEADER = '.tab-collection ul li a';
    const SELECTOR_TAB_CONTENT = '.tab-content';

    /**
     * @param string $tabName
     * @return bool
     */
    public function hasTab($tabName)
    {
        return (bool) $this->findTabHeaderByName($tabName);
    }

    /**
     * @param string $tabName
     */
    public function switchToTab($tabName)
    {
        $tabHeader = $this->findTabHeaderByName($tabName);
        $tabHeader->click();
    }

    /**
     * @return NodeElement|null
     */
    public function getActiveTab()
    {
        return $this->findVisible('css', self::SELECTOR_TAB_CONTENT);
    }

    /**
     * @param string $tabName
     * @return NodeElement|null
     */
    protected function findTabHeaderByName($tabName)
    {
        return $this->find(
            'css',
            $this->selectorManipulator->addContainsSuffix(self::SELECTOR_TAB_HEADER, $tabName)
        );
    }
}
