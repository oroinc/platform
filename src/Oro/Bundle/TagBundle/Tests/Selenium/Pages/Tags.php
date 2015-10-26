<?php

namespace Oro\Bundle\TagBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Tags
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages\Objects
 * @method Tags openTags(string $bundlePath)
 * @method Tag open(array $filter)
 * @method Tag add()
 * {@inheritdoc}
 */
class Tags extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Tag']";
    const URL = 'tag';

    public function entityNew()
    {
        return new Tag($this->test);
    }

    public function entityView()
    {
        return new Tag($this->test);
    }

    /**
     * @return Tag
     */
    public function edit()
    {
        if ($this->isElementPresent("//td[contains(@class,'action-cell')]//a[contains(., '...')]")) {
            $menu = $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]");
            $this->test->moveto($menu);
        }
        $this->test->byXpath("//td[contains(@class,'action-cell')]//a[@title= 'Edit']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Tag($this->test);
    }

    public function checkContextMenu($tagName, $contextName)
    {
        $this->filterBy('Tag', $tagName);
        if ($this->isElementPresent("//td[contains(@class,'action-cell')]//a[contains(., '...')]")) {
            $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]")->click();
            $this->waitForAjax();
        }
        $this->assertElementNotPresent("//td[contains(@class,'action-cell')]//a[@title= '{$contextName}']");
    }
}
