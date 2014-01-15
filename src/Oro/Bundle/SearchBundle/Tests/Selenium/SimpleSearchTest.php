<?php

namespace Oro\Bundle\SearchBundle\Tests\Selenium;

use Oro\Bundle\SearchBundle\Tests\Selenium\Pages\Search;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class SimpleSearchTest extends Selenium2TestCase
{
    public function testSearchSuggestions()
    {
        $this->login();
        $search = new Search($this);
        //fill-in simple search field
        $result = $search->search('admin@example.com')
            ->suggestions('admin');
        $this->assertNotEmpty($result, 'No search suggestions available');
    }

    public function testSearchResult()
    {
        $this->login();
        $search = new Search($this);
        $result = $search->search('admin')
            ->submit()
            ->result('John Doe');
        $this->assertNotEmpty($result);
    }
}
