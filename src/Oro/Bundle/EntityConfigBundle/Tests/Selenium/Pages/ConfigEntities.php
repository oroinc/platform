<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class ConfigEntities
 *
 * @package Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages
 * @method ConfigEntities openConfigEntities() openConfigEntities(string)
 * @method ConfigEntity add() add()
 * @method ConfigEntity open() open()
 */
class ConfigEntities extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create entity']";
    const URL = 'entity/config/';

    public function entityNew()
    {
        $entity = new ConfigEntity($this->test);
        return $entity->init(true);
    }

    public function entityView()
    {
        return new ConfigEntity($this->test);
    }

    public function delete()
    {
        $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]")->click();
        $this->waitForAjax();
        $this->test->byXpath("//td[contains(@class,'action-cell')]//a[@title= 'Remove']")->click();
        $this->waitPageToLoad();
        return $this;
    }
}
