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
        $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_user_group_form_owner']"))
            ->selectOptionByLabel($owner);

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
