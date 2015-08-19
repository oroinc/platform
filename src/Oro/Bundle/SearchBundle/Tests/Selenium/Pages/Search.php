<?php

namespace Oro\Bundle\SearchBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;
use PHPUnit_Framework_Assert;

/**
 * Class Search
 *
 * @package Oro\Bundle\SearchBundle\Tests\Selenium\Pages
 * @method Search openSearch()
 * @method Search assertTitle($title, $message = '')
 */
class Search extends AbstractPage
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $simpleSearch;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $searchButton;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $pane;

    public function __construct($testCase)
    {
        parent::__construct($testCase);
        $this->pane = $this->test->byXpath('//a[@title="Search"]');
        $this->simpleSearch = $this->test->byId('search-bar-search');
        $this->searchButton = $this->test->byXpath("//form[@id='top-search-form']//div/button[contains(.,'Go')]");
    }

    /**
     * @param string $value
     * @return $this
     */
    public function search($value)
    {
        if (!$this->simpleSearch->displayed()) {
            $this->pane->click();
        }
        $this->simpleSearch->clear();
        $this->simpleSearch->value($value);
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param null|string $filter
     * @return mixed
     */
    public function suggestions($filter = null)
    {
        if (!is_null($filter)) {
            $result = $this->test->elements(
                $this->test->using("xpath")->value("//div[@class='header-search-frame']//a[contains(., '{$filter}')]")
            );
        } else {
            $result = $this->test->elements(
                $this->test->using("xpath")->value("//div[@id='search-dropdown']/ul/li/a")
            );
        }

        return $result;
    }

    /**
     * @param string $filter
     * @return $this
     * @throws \Exception
     */
    public function select($filter)
    {
        $result = $this->result($filter);
        PHPUnit_Framework_Assert::assertInternalType('array', $result);
        PHPUnit_Framework_Assert::assertNotEmpty($result);
        $found = current($this->result($filter));
        $found->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $filter
     * @return mixed
     */
    public function result($filter)
    {
        if (!is_null($filter)) {
            $result = $this->test->elements(
                $this->test->using("xpath")->value(
                    "//div[@id='search-result-grid']//tr//h1/a[normalize-space(.) = '{$filter}']"
                )
            );
        } else {
            $result = $this->test->elements(
                $this->test->using("xpath")->value("//div[@id='search-result-grid']//tr//h1/a")
            );
        }

        return $result;
    }

    public function submit()
    {
        $this->searchButton->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param string $entityType
     * @param string $entityCount
     * @return $this
     */
    public function assertEntity($entityType, $entityCount)
    {
        $this->assertElementPresent(
            "//li//a[contains(., '{$entityType} ({$entityCount})')]"
        );

        return $this;
    }
}
