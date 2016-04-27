<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

class Role extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $accessLevel;

    public function setLabel($label)
    {
        $this->test->byXpath("//*[@data-ftid='oro_user_role_form_label']")->value($label);
        return $this;
    }

    public function getLabel()
    {
        return $this->test->byXpath("//*[@data-ftid='oro_user_role_form_label']")->value();
    }

    /**
     * @param $entityName string of ACL resource name
     * @param $aclAction array of actions such as create, edit, delete, view, assign
     * @param $accessLevel string
     *
     * @return $this
     */
    public function setEntity($entityName, $aclAction, $accessLevel)
    {
        foreach ($aclAction as $action) {
            $action = strtoupper($action);
            $this->accessLevel = $this->test->byXpath(
                "//div[strong/text() = '{$entityName}']/ancestor::tr//input" .
                "[contains(@name, '[$action][accessLevel')]/preceding-sibling::div/a"
            );
            $this->test->moveto($this->accessLevel);
            $this->accessLevel->click();
            $this->waitForAjax();
            if ($accessLevel === 'System'
                && !$this->isElementPresent("//div[@id='select2-drop']//div[contains(., '{$accessLevel}')]")) {
                $accessLevel = 'Organization';
            }
            $this->test->byXPath("//div[@id='select2-drop']//div[contains(., '{$accessLevel}')]")->click();
        }

        return $this;
    }

    /**
     * @param $capabilityName array of Capability ACL resources
     * @param $accessLevel
     *
     * @return $this
     */
    public function setCapability($capabilityName, $accessLevel)
    {
        foreach ($capabilityName as $name) {
            $xpath = $this->test->byXpath(
                "//div[strong/text() = '{$name}']/following-sibling::div//a"
            );
            $this->test->moveto($xpath);
            $xpath->click();
            $this->waitForAjax();
            $this->accessLevel = $this->test->select(
                $this->test->byXpath("//div[strong/text() = '{$name}']/following-sibling::div//select")
            );
            $this->accessLevel->selectOptionByLabel($accessLevel);
        }

        return $this;
    }
}
