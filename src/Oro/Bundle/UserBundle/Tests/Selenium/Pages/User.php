<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class User
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method Users openUsers(string $bundlePath)
 * @method User openUser(string $bundlePath)
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
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $encryption;

    public function init($new = false)
    {
        $this->username = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_username']");
        if ($new) {
            $this->firstPassword = $this->test
                ->byXpath("//*[@data-ftid='oro_user_user_form_plainPassword_first']");
            $this->secondPassword = $this->test
                ->byXpath("//*[@data-ftid='oro_user_user_form_plainPassword_second']");
        }
        $this->enabled = $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_user_user_form_enabled']"));
        $this->firstName = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_firstName']");
        $this->lastName = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_lastName']");
        $this->middleName = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_middleName']");
        $this->email = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_email']");
        $this->groups = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_groups']");
        $this->roles = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_roles']");
        $this->owner = $this->test->select($this->test->byXpath("//*[@data-ftid='oro_user_user_form_owner']"));
        $this->inviteUser = $this->test->byXpath("//*[@data-ftid='oro_user_user_form_inviteUser']");

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

    public function setMiddleName($name)
    {
        $this->middleName->clear();
        $this->middleName->value($name);
        return $this;
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
        if ($this->isElementPresent("//div[starts-with(@id,'s2id_oro_user_user_form_tags_autocomplete')]")) {
            $this->tags = $this->test
                ->byXpath("//div[starts-with(@id,'s2id_oro_user_user_form_tags_autocomplete')]//input");
            $this->tags->click();
            $this->tags->value(substr($tag, 0, (strlen($tag)-1)));
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocomplete doesn't return entity"
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
        if ($this->isElementPresent("//div[starts-with(@id,'s2id_oro_user_user_form_tags_autocomplete')]")) {
            $this->tags = $this->test
                ->byXpath("//div[starts-with(@id,'s2id_oro_user_user_form_tags_autocomplete')]//input");
            $this->tags->click();
            $this->tags->value($tag);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocomplete doesn't return entity"
            );
            $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$tag}')]")->click();

            return $this;
        } else {
            throw new \Exception("Tag field can't be found");
        }
    }

    /**
     * @param array $roles
     * @param bool  $oneOf Do not check role exists or not
     *
     * @return $this
     */
    public function setRoles($roles = array(), $oneOf = false)
    {
        $condition = '';
        if ($oneOf) {
            foreach ($roles as $role) {
                if ($condition != '') {
                    $condition .= ' or ';
                }
                $condition .= "contains(., '{$role}')";
            }
            $element = $this->roles->element(
                $this->test->using('xpath')->value(
                    "//div[@data-ftid='oro_user_user_form_roles']/div[label[{$condition}]]/input"
                )
            );
            $this->test->moveto($element);
            $element->click();
        } else {
            foreach ($roles as $role) {
                $element = $this->roles->element(
                    $this->test->using('xpath')->value(
                        "//div[@data-ftid='oro_user_user_form_roles']".
                        "/div[label[contains(normalize-space(text()), '{$role}')]]/input"
                    )
                );
                $this->test->moveto($element);
                $element->click();
            }
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

    /**
     * This method can set Business units and Organizations
     * @param array $businessUnits
     * @return $this
     */
    public function setBusinessUnit($businessUnits = array('Main'))
    {
        foreach ($businessUnits as $businessUnit) {
            $this->test->byXpath(
                "//div[@data-ftid='oro_user_user_form_organizations']//label[contains(., '{$businessUnit}')]".
                "/preceding-sibling::input"
            )->click();
        }

        return $this;
    }

    public function edit()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit User']")->click();
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
            "//div[label[normalize-space(text()) = 'Username']]//div/div[normalize-space(text()) = '$userName']"
        );
        return $this;
    }

    public function checkRoleSelector()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit profile']")->click();
        $this->waitPageToLoad();
        $this->assertElementPresent(
            "//div[@data-ftid='oro_user_user_form_roles']//input[@checked='checked' and @disabled='disabled']",
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
            "//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-draggable ui-resizable " .
            "ui-dialog-normal']"
        );
        $this->test->byXpath(
            "//div[@class='ui-dialog-titlebar-buttonpane']/button[@title='close']"
        )->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-draggable ui-resizable " .
            "ui-dialog-normal']"
        );

        return $this;
    }

    /**
     * Method configure user IMAP sync
     * @param array $imapSetting
     * @return $this
     */
    public function setImap($imapSetting)
    {
        $this->test->byXpath(
            "//div[@class='control-group imap-config check-connection control-group-checkbox']" .
            "//input[@data-ftid='oro_user_user_form_imapConfiguration_useImap']"
        )->click();
        $this->waitForAjax();
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_user_form_imapConfiguration_imapHost']"
        )->value($imapSetting['host']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_user_form_imapConfiguration_imapPort']"
        )->value($imapSetting['port']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_user_form_imapConfiguration_user']"
        )->value($imapSetting['user']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_user_form_imapConfiguration_password']"
        )->value($imapSetting['password']);
        $this->encryption = $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_user_user_form_imapConfiguration_imapEncryption']"));
        $this->encryption->selectOptionByLabel($imapSetting['encryption']);
        $this->test->byXPath("//button[@id='oro_user_user_form_imapConfiguration_check_connection']")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->test->byXPath("//div[@class='control-group folder-tree']//input[@id='check-all']")->click();

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSignature($value)
    {
        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                return $testCase->execute(
                    [
                        'script' => 'return tinyMCE.activeEditor.initialized',
                        'args' => [],
                    ]
                );
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->execute(
            [
                'script' => sprintf('tinyMCE.activeEditor.setContent(\'%s\')', $value),
                'args' => [],
            ]
        );

        return $this;
    }

    /**
     * Method changes password using actions menu form user view page
     * @param $newPassword
     * @return $this
     */
    public function changePassword($newPassword)
    {
        $passwordField = "//*[@data-ftid='oro_set_password_form_password']";
        $this->runActionInGroup('Change password');
        $this->waitForAjax();
        $this->test->byXPath($passwordField)->clear();
        $this->test->byXPath($passwordField)->value($newPassword);
        $this->test->byXPath("//div[@class='widget-actions-section']//button[@type='submit']")->click();
        $this->waitForAjax();
        $this->assertMessage('The password has been changed');

        return $this;
    }
}
