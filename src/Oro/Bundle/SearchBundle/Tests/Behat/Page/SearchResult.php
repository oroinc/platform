<?php

namespace Oro\Bundle\SearchBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class SearchResult extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Dashboards/ Dashboard');
        $page = $this->elementFactory->getPage();
        $page->clickLink('Search');
        $field = $page->find('css', 'input#search-bar-search');

        /** @var OroSelenium2Driver $driver */
        $driver = $page->getSession()->getDriver();
        $driver->typeIntoInput($field->getXpath(), $parameters['searchString']);
    }
}
