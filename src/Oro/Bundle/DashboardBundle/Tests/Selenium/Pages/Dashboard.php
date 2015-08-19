<?php

namespace Oro\Bundle\DashboardBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Dashboard
 *
 * @package Oro\Bundle\DashboardBundle\Tests\Selenium\Pages
 */
class Dashboard extends AbstractPageEntity
{
    /** @var string */
    protected $owner = "//div[starts-with(@id,'s2id_oro_dashboard_owner')]/a";

    public function setLabel($value)
    {
        $label = $this->test->byXpath("//*[@data-ftid='oro_dashboard_label']");
        $label->clear();
        $label->value($value);

        return $this;
    }

    public function getLabel()
    {
        return $this->test->byXpath("//*[@data-ftid='oro_dashboard_label']")->value();
    }

    public function setClone($value)
    {
        $clone = $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_dashboard_startDashboard']"));
        $clone->selectOptionByLabel($value);

        return $this;
    }
}
