<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class User
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users openUsers() openUsers()
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\User assertTitle() assertTitle($title, $message = '')
 */
class User extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $username;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $enabled;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $firstPassword;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $secondPassword;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $firstName;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $lastName;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $middleName;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $email;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $dob;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $avatar;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $groups;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $roles;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $owner;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $company;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $salary;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $address;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $gender;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $website;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tags;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $inviteUser;

    public function init($new = false)
    {
        $this->username = $this->test->byId('oro_user_user_form_username');
        if ($new) {
            $this->firstPassword = $this->test->byId('oro_user_user_form_plainPassword_first');
            $this->secondPassword = $this->test->byId('oro_user_user_form_plainPassword_second');
        }
        $this->enabled = $this->test->select($this->test->byId('oro_user_user_form_enabled'));
        $this->firstName = $this->test->byId('oro_user_user_form_firstName');
        $this->lastName = $this->test->byId('oro_user_user_form_lastName');
        $this->email = $this->test->byId('oro_user_user_form_email');
        $this->groups = $this->test->byId('oro_user_user_form_groups');
        $this->roles = $this->test->byId('oro_user_user_form_roles');
        $this->owner = $this->test->select($this->test->byId('oro_user_user_form_owner'));
        $this->inviteUser = $this->test->byId('oro_user_user_form_inviteUser');

        return $this;
    }

    public function uncheckInviteUser()
    {
        $this->inviteUser->click();

        return $this;
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

    public function setUsername($name)
    {
        $this->username->clear();
        $this->username->value($name);
        return $this;
    }

    public function getName()
    {
        return $this->username->value();
    }

    public function enable()
    {
        $this->enabled->selectOptionByLabel('Active');
        return $this;
    }

    public function disable()
    {
        $this->enabled->selectOptionByLabel('Inactive');
        return $this;
    }

    public function setFirstPassword($password)
    {
        $this->firstPassword->clear();
        $this->firstPassword->value($password);
        return $this;
    }

    public function getFirstPassword()
    {
        return $this->firstPassword->value();
    }

    public function setSecondPassword($password)
    {
        $this->secondPassword->clear();
        $this->secondPassword->value($password);
        return $this;
    }

    public function getSecondPassword()
    {
        return $this->secondPassword->value();
    }

    public function setFirstName($name)
    {
        $this->firstName->clear();
        $this->firstName->value($name);
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstName->value();
    }

    public function setLastName($name)
    {
        $this->lastName->clear();
        $this->lastName->value($name);
        return $this;
    }

    public function getLastName()
    {
        return $this->lastName->value();
    }

    public function setEmail($email)
    {
        $this->email->clear();
        $this->email->value($email);
        return $this;
    }

    public function getEmail()
    {
        return $this->email->value();
    }

    public function verifyTag($tag)
    {
        if ($this->isElementPresent("//div[@id='s2id_oro_user_user_form_tags_autocomplete']")) {
            $this->tags = $this->test->byXpath("//div[@id='s2id_oro_user_user_form_tags_autocomplete']//input");
            $this->tags->click();
            $this->tags->value(substr($tag, 0, (strlen($tag)-1)));
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocoplete doesn't return entity"
            );
            $this->tags->clear();
        } else {
            if ($this->isElementPresent("//div[contains(@class, 'tags-holder')]")) {
                $this->assertElementPresent(
                    "//div[contains(@class, 'tags-holder')]//li[contains(., '{$tag}')]",
                    'Tag is not assigned to entity'
                );
            } else {
                throw new \Exception("Tag field can't be found");
            }
        }
        return $this;
    }

    /**
     * @param $tag
     * @return $this
     * @throws \Exception
     */
    public function setTag($tag)
    {
        if ($this->isElementPresent("//div[@id='s2id_oro_user_user_form_tags_autocomplete']")) {
            $this->tags = $this->test->byXpath("//div[@id='s2id_oro_user_user_form_tags_autocomplete']//input");
            $this->tags->click();
            $this->tags->value($tag);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocoplete doesn't return entity"
            );
            $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$tag}')]")->click();

            return $this;
        } else {
            throw new \Exception("Tag field can't be found");
        }
    }

    public function setRoles($roles = array())
    {
        foreach ($roles as $role) {
            $this->roles->element(
                $this->test->using('xpath')->value("div[label[normalize-space(text()) = '{$role}']]/input")
            )->click();
        }

        return $this;

    }

    public function setGroups($groups = array())
    {
        foreach ($groups as $group) {
            $this->groups->element(
                $this->test->using('xpath')->value("div[label[normalize-space(text()) = '{$group}']]/input")
            )->click();
        }

        return $this;
    }

    public function edit()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit user']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->init();
        return $this;
    }

    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new Users($this->test, false);
    }

    public function viewInfo($userName)
    {
        $this->test->byXpath("//ul[@class='nav pull-right user-menu']//a[@class='dropdown-toggle']")->click();
        $this->waitForAjax();
        $this->test->byXpath("//ul[@class='dropdown-menu']//a[contains(normalize-space(.), 'My User')]")->click();
        $this->waitPageToLoad();
        $this->assertElementPresent(
            "//div[label[normalize-space(text()) = 'Username']]//div/p[normalize-space(text()) = '$userName']"
        );
        return $this;
    }

    public function checkRoleSelector()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit profile']")->click();
        $this->waitPageToLoad();
        $this->assertElementPresent(
            "//div[@id='oro_user_user_form_roles']//input[@checked='checked' and @disabled='disabled']",
            'Role selector are not disabled for user'
        );
    }

    public function checkHistoryWindow()
    {
        $this->test->byXpath(
            "//div[@class='navigation clearfix navbar-extra navbar-extra-right']//a[contains(., 'Change History')]"
        )->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all ".
            "ui-front ui-draggable ui-resizable ui-dialog-normal ui-dialog-buttons']"
        );
        $this->test->byXpath(
            "//div[@class='ui-dialog-titlebar-buttonpane']/button[@title='close']"
        )->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all " .
            "ui-front ui-draggable ui-resizable ui-dialog-normal ui-dialog-buttons']"
        );

        return $this;
    }
}
