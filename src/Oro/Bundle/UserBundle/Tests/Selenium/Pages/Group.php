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
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $name;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select  */
    protected $owner;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
        $this->name = $this->test->byXpath("//*[@data-ftid='oro_user_group_form_name']");
        $this->owner = $this->test->select($this->test->byXpath("//*[@data-ftid='oro_user_group_form_owner']"));
    }

    public function setName($name)
    {
        $this->name->value($name);
        return $this;
    }

    public function getName()
    {
        return $this->name->value();
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
