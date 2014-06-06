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
        $this->label = $this->test->byId('oro_dashboard_label');
        $this->owner = $this->test->select($this->test->byId('oro_user_group_form_owner'));
    }

    public function init()
    {
        $this->clone = $this->test->select($this->test->byId('oro_dashboard_startDashboard'));
        return $this;
    }

    public function setLabel($label)
    {
        $this->label->value($label);
        return $this;
    }

    public function getLabel()
    {
        return $this->label->value();
    }

    public function setOwner($owner)
    {
        $this->owner->selectOptionByLabel($owner);

        return $this;
    }

    public function getOwner()
    {
        return trim($this->owner->selectedLabel());
    }
}
