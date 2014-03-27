<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

class TransactionEmail extends AbstractPageEntity
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select  */
    protected $entityName;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select  */
    protected $event;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $template;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $user;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $groups;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $email;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
        $this->entityName = $this->test->select($this->test->byId('emailnotification_entityName'));
        $this->event = $this->test->select($this->test->byId('emailnotification_event'));
        $this->template = $this->test->byXpath("//div[@id='s2id_emailnotification_template']/a");
        $this->user = $this->test->byXpath("//div[@id='s2id_emailnotification_recipientList_users']//input");
        $this->groups = $this->test->byId('emailnotification_recipientList_groups');
        $this->email = $this->test->byId('emailnotification_recipientList_email');
    }

    /**
     * @param $entityName
     * @return $this
     */
    public function setEntityName($entityName)
    {
        $this->entityName->selectOptionByLabel($entityName);
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param $event
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event->selectOptionByLabel($event);
        return $this;
    }

    /**
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template->click();
        $this->waitForAjax();
        $this->test->byXpath("//div[@id='select2-drop']/div/input")->value($template);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$template}')]",
            "Template autocomplete doesn't return search value"
        );
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$template}')]")->click();

        return $this;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user->click();
        $this->user->value($user);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$user}')]",
            "Users autocomplete field doesn't return entity"
        );
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$user}')]")->click();

        return $this;
    }

    /**
     * @param array $groups
     * @return $this
     */
    public function setGroups($groups = array())
    {
        foreach ($groups as $group) {
            $this->groups->element(
                $this->test->using('xpath')->value("div[label[normalize-space(text()) = '{$group}']]/input")
            )->click();
        }

        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email->clear();
        $this->email->value($email);

        return $this;
    }
}
