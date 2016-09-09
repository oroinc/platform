<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Group
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 */
class Group extends AbstractPageEntity
{
    public function setName($name)
    {
        $this->test->byXpath("//*[@data-ftid='oro_user_group_form_name']")->value($name);
        return $this;
    }

    public function getName()
    {
        return $this->test->byXpath("//*[@data-ftid='oro_user_group_form_name']")->value();
    }

    public function setOwner($owner)
    {
        $this->test->byXpath('//*[@data-ftid="oro_user_group_form_owner"]/preceding-sibling::div/a')->click();
        $this->waitForAjax();
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$owner}')]")->click();

        return $this;
    }

    public function getOwner()
    {
        return trim(
            $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_user_group_form_owner']"))
            ->selectedLabel()
        );
    }
}
