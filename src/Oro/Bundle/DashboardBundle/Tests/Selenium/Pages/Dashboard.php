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
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $label;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select  */
    protected $owner;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select  */
    protected $clone;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
        $this->label = $this->test->byXpath("//*[@data-ftid='oro_dashboard_label']");
        $this->owner = $this->test->byXpath("//div[starts-with(@id,'s2id_oro_dashboard_owner')]/a");
    }

    public function init()
    {
        $this->clone = $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_dashboard_startDashboard']"));
        return $this;
    }

    public function setLabel($label)
    {
        $this->label->clear();
        $this->label->value($label);
        return $this;
    }

    public function getLabel()
    {
        return $this->label->value();
    }

    public function setOwner($owner)
    {
        $this->owner->click();
        $this->waitForAjax();
        $this->test->byXpath("//div[@id='select2-drop']/div/input")->value($owner);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$owner}')]",
            "Owner autocomplete doesn't return search value"
        );
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$owner}')]")->click();

        return $this;
    }

    public function getOwner()
    {
        return ;
    }

    public function setClone($clone)
    {
        $this->clone->selectOptionByLabel($clone);

        return $this;
    }
}
