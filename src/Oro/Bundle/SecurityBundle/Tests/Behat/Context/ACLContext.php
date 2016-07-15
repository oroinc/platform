<?php

namespace Oro\Bundle\SecurityBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserRoleForm;

class ACLContext extends RawMinkContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @Given /^(?:|I am )logged in under (?P<organization>(\D*)) organization$/
     */
    public function iAmLoggedInUnderSystemOrganization($organization)
    {
        $page = $this->getSession()->getPage();
        $page->find('css', '.btn-organization-switcher')->click();
        $page->find('css', '.dropdown-organization-switcher')->clickLink($organization);
    }

    //@codingStandardsIgnoreStart
    /**
     * Set access level for action for specified entity for admin user
     * Example: Given my permissions on Delete Cases is set to System
     * @Given /^(?:|I )have "(?P<accessLevel>(?:[^"]|\\")*)" permissions for "(?P<action>(?:[^"]|\\")*)" "(?P<entity>(?:[^"]|\\")*)" entity$/
     * @Given /^my permissions on (?P<action1>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) is set to (?P<accessLevel>(?:[^"]|\\")*)$/
     * @When /^(?:|I )set my permissions on (?P<action1>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) to (?P<accessLevel>(?:[^"]|\\")*)$/
     */
    //@codingStandardsIgnoreEnd
    public function iHavePermissionsForEntity($entity, $action, $accessLevel)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $singularizedEntity = ucfirst(Inflector::singularize($entity));
        $this->loginAsAdmin();
        $this->openRoleEditForm('Administrator');
        /** @var UserRoleForm $userRoleForm */
        $userRoleForm = $this->elementFactory->createElement('UserRoleForm');
        $userRoleForm->setPermission($singularizedEntity, $action, $accessLevel);
        $userRoleForm->saveAndClose();
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    //@codingStandardsIgnoreStart
    /**
     * Set access level for several actions for specified entity for admin user
     * Example: Given my permissions on View Accounts as User and on Delete as System
     * Example: Given my permissions on View Cases as System and on Delete as User
     * @Given /^my permissions on (?P<action1>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) as (?P<accessLevel1>(?:[^"]|\\")*) and on (?P<action2>(?:|View|Create|Edit|Delete|Assign|Share)) as (?P<accessLevel2>(?:[^"]|\\")*)$/
     */
    //@codingStandardsIgnoreEnd
    public function iHaveSeveralPermissionsForEntity($entity, $action1, $accessLevel1, $action2, $accessLevel2)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $singularizedEntity = ucfirst(Inflector::singularize($entity));
        $this->loginAsAdmin();
        $this->openRoleEditForm('Administrator');
        /** @var UserRoleForm $userRoleForm */
        $userRoleForm = $this->elementFactory->createElement('UserRoleForm');
        $userRoleForm->setPermission($singularizedEntity, $action1, $accessLevel1);
        $userRoleForm->setPermission($singularizedEntity, $action2, $accessLevel2);
        $userRoleForm->saveAndClose();
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    protected function loginAsAdmin()
    {
        $this->visitPath('/user/login');
        /** @var Form $login */
        $login = $this->createElement('Login');
        $login->fill(new TableNode([['Username', 'admin'], ['Password', 'admin']]));
        $login->pressButton('Log in');
        $this->getSession()->getDriver()->waitForAjax();
    }

    /**
     * @param $role
     */
    protected function openRoleEditForm($role)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/ User Management/ Roles');
        $this->getSession()->getDriver()->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($role, 'Edit');
        $this->getSession()->getDriver()->waitForAjax();
    }
}
