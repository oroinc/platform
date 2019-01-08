<?php

namespace Oro\Bundle\SecurityBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterStringItem;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserRoleForm;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserRoleViewForm;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ACLContext extends OroFeatureContext implements
    OroPageObjectAware,
    KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /** @var OroMainContext */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * This is perform the change the organization from dashboard
     * It's need to be authenticated before perform this step
     * Example: Given I login as administrator
     *          And I am logged in under System organization
     *
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
     * Set access level for action for specified entity for role
     * Two roles is supported - User and Administrator
     * Example: Given user permissions on Create Account is set to None
     * Example: And user have "User" permissions for "View" "Business Customer" entity
     * Example: And I set administrator permissions on Delete Cases to None
     *
     * @Given /^(?P<user>(administrator|user)) have "(?P<accessLevel>(?:[^"]|\\")*)" permissions for "(?P<action>(?:[^"]|\\")*)" "(?P<entity>(?:[^"]|\\")*)" entit(y|ies)$/
     * @Given /^(?P<user>(administrator|user)) permissions on (?P<action>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) is set to (?P<accessLevel>(?:[^"]|\\")*)$/
     * @When /^(?:|I )set (?P<user>(administrator|user)) permissions on (?P<action>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) to (?P<accessLevel>(?:[^"]|\\")*)$/
     */
    //@codingStandardsIgnoreEnd
    public function iHavePermissionsForEntity($entity, $action, $accessLevel, $user)
    {
        $role = $this->getRole($user);
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $singularizedEntities = array_map(function ($element) {
            return trim(ucfirst(Inflector::singularize($element)));
        }, explode(',', $entity));

        $this->loginAsAdmin();

        $userRoleForm = $this->openRoleEditForm($role);

        foreach ($singularizedEntities as $singularizedEntity) {
            $userRoleForm->setPermission($singularizedEntity, $action, $accessLevel);
        }

        $userRoleForm->saveAndClose();
        $this->waitForAjax();

        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }


    /**
     * @Given /^(?P<user>(administrator|user)) has following permissions$/
     */
    public function userHasFollowingPermissions($user, TableNode $table)
    {
        $role = $this->getRole($user);
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $this->loginAsAdmin();
        $this->waitForAjax();

        $userRoleForm = $this->openRoleEditForm($role);

        foreach ($table->getRows() as $row) {
            $action = $row[0];
            $singularizedEntity = trim(ucfirst(Inflector::singularize($row[1])));
            $accessLevel = $row[2];
            $userRoleForm->setPermission($singularizedEntity, $action, $accessLevel);
        }

        $userRoleForm->saveAndClose();
        $this->waitForAjax();

        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @Given /^(?P<user>(administrator|user)) has following entity permissions enabled$/
     */
    public function userHasFollowingEntityPermissionsEnabled($user, TableNode $table)
    {
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $this->loginAsAdmin();

        $role = $this->getRole($user);
        $userRoleForm = $this->openRoleEditForm($role);

        foreach ($table->getRows() as $row) {
            $name = $row[0];
            $userRoleForm->setCheckBoxPermission($name);
        }

        $userRoleForm->saveAndClose();
        $this->waitForAjax();

        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    //@codingStandardsIgnoreStart
    /**
     * Set access level for several actions for specified entity for role
     * Two roles is supported - User and Administrator
     *
     * Example: Given user permissions on View Accounts as User and on Delete as System
     * Example: Given administrator permissions on View Cases as System and on Delete as User
     *
     * @Given /^(?P<user>(administrator|user)) permissions on (?P<action1>(?:|View|Create|Edit|Delete|Assign|Share)) (?P<entity>(?:[^"]|\\")*) as (?P<accessLevel1>(?:[^"]|\\")*) and on (?P<action2>(?:|View|Create|Edit|Delete|Assign|Share)) as (?P<accessLevel2>(?:[^"]|\\")*)$/
     */
    //@codingStandardsIgnoreEnd
    public function iHaveSeveralPermissionsForEntity($user, $entity, $action1, $accessLevel1, $action2, $accessLevel2)
    {
        $role = $this->getRole($user);
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $singularizedEntity = ucfirst(Inflector::singularize($entity));
        $this->loginAsAdmin();

        $userRoleForm = $this->openRoleEditForm($role);
        $userRoleForm->setPermission($singularizedEntity, $action1, $accessLevel1);
        $userRoleForm->setPermission($singularizedEntity, $action2, $accessLevel2);
        $userRoleForm->saveAndClose();
        $this->waitForAjax();

        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * Change group of permissions on create/edit pages
     *
     * Example: And select following permissions:
     *       | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
     *       | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User    |
     *
     * @Then /^(?:|I )select following permissions:$/
     */
    public function iSelectFollowingPermissions(TableNode $table)
    {
        $userRoleForm = $this->getRoleEditFormElement();

        foreach ($table->getRows() as $row) {
            $entityName = array_shift($row);

            foreach ($row as $cell) {
                list($role, $value) = explode(':', $cell);
                $userRoleForm->setPermission($entityName, $role, $value);
            }
        }
    }

    /**
     * Set capability permission on create/edit pages by selecting checkbox
     *
     * Example: And I check "Access dotmailer statistics" entity permission
     *
     * @Then /^(?:|I )check "(?P<name>(?:[^"]|\\")*)" entity permission$/
     */
    public function checkEntityPermission($name)
    {
        $userRoleForm = $this->getRoleEditFormElement();
        $userRoleForm->setCheckBoxPermission($name);
    }

    /**
     * Example: And I uncheck "Access dotmailer statistics" entity permission
     *
     * @Then /^(?:|I )uncheck "(?P<name>(?:[^"]|\\")*)" entity permission$/
     */
    public function uncheckEntityPermission($name)
    {
        $userRoleForm = $this->getRoleEditFormElement();
        $userRoleForm->setCheckBoxPermission($name, false);
    }

    /**
     * Asserts that provided permissions allowed on role view page
     *
     * Example: Then the role has following active permissions:
     *            | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
     *            | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
     *
     * @Then /^the role has following active permissions:$/
     */
    public function iSeeFollowingPermissions(TableNode $table)
    {
        $userRoleForm = $this->getRoleViewFormElement();
        $permissionsArray = $userRoleForm->getPermissions();
        foreach ($table->getRows() as $row) {
            $entityName = array_shift($row);

            foreach ($row as $cell) {
                list($role, $value) = explode(':', $cell);
                self::assertNotEmpty($permissionsArray[$entityName][$role]);
                $expected = $permissionsArray[$entityName][$role];
                self::assertEquals(
                    $expected,
                    $value,
                    "Failed asserting that permission $expected equals $value for $entityName"
                );
            }
        }
    }

    /**
     * Example: Then the role has not following active permissions:
     *            | Language | View | Create | Edit | Assign | Translate |
     *
     * @Then /^the role has not following permissions:$/
     *
     * @param TableNode $table
     */
    public function iDontSeeFollowingPermissions(TableNode $table)
    {
        $userRoleForm = $this->getRoleViewFormElement();
        $permissionsArray = $userRoleForm->getPermissions();
        foreach ($table->getRows() as $row) {
            $entityName = array_shift($row);

            foreach ($row as $value) {
                self::assertFalse(
                    isset($permissionsArray[$entityName][$value]),
                    "Failed asserting that permission $value not present for $entityName"
                );
            }
        }
    }

    /**
     * Asserts that provided capability permissions allowed on view page
     *
     * Example: And following capability permissions should be checked:
     *           | Access system information |
     *
     * @Then /^following capability permissions should be checked:$/
     */
    public function iShouldSeePermissionsChecked(TableNode $table)
    {
        $userRoleForm = $this->getRoleViewFormElement();
        $permissions = $userRoleForm->getEnabledCapabilityPermissions();

        foreach ($table->getRows() as $row) {
            $value = current($row);
            self::assertContains(
                ucfirst($value),
                $permissions,
                "$value not found in active permissions list: " . print_r($permissions, true)
            );
        }
    }

    /**
     * Asserts that provided capability permissions disallowed on view page
     *
     * Example: And following capability permissions should be unchecked:
     *           | Access system information |
     *
     * @Then /^following capability permissions should be unchecked:$/
     */
    public function iShouldSeePermissionsUnchecked(TableNode $table)
    {
        $userRoleForm = $this->getRoleViewFormElement();
        $permissions = $userRoleForm->getEnabledCapabilityPermissions();

        foreach ($table->getRows() as $row) {
            $value = current($row);
            self::assertNotContains(
                ucfirst($value),
                $permissions,
                "$value found in active permissions list: " . print_r($permissions, true)
            );
        }
    }

    /**
     * @Then /^(?:|I )click update schema$/
     */
    public function iClickUpdateSchema()
    {
        try {
            $page = $this->getPage();

            $page->clickLink('Update schema');
            $this->waitForAjax();
            $page->clickLink('Yes, Proceed');
            $this->waitForAjax();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Click edit entity button on entity view page
     * Example: Given I'm edit entity
     *
     * @Given /^(?:|I |I'm )edit entity$/
     */
    public function iMEditEntity()
    {
        $this->createElement('Entity Edit Button')->click();
    }

    protected function loginAsAdmin()
    {
        $this->oroMainContext->loginAsUserWithPassword();
        $this->waitForAjax();
    }

    /**
     * @param $role
     * @return UserRoleForm
     */
    protected function openRoleEditForm($role)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/ User Management/ Roles');
        $this->waitForAjax();

        $this->createElement('GridFiltersButton')->open();

        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this->createElement('GridFilters')->getFilterItem('GridFilterStringItem', 'Label');

        $filterItem->open();
        $filterItem->selectType('is equal to');
        $filterItem->setFilterValue($role->__toString());
        $filterItem->submit();
        $this->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');

        $grid->clickActionLink($role, 'Edit');
        $this->waitForAjax();

        return $this->getRoleEditFormElement();
    }

    /**
     * @param string $user
     * @return Role
     * @throws ExpectationException
     */
    protected function getRole($user)
    {
        if ('administrator' === $user) {
            return $this->getContainer()
                ->get('oro_entity.doctrine_helper')
                ->getEntityRepositoryForClass(Role::class)
                ->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
        } elseif ('user' === $user) {
            return $this->getContainer()
                ->get('oro_entity.doctrine_helper')
                ->getEntityRepositoryForClass(Role::class)
                ->findOneBy(['role' => User::ROLE_DEFAULT]);
        }

        throw new ExpectationException(
            "Unexpected user '$user' for role permission changes",
            $this->getSession()->getDriver()
        );
    }

    /**
     * @return UserRoleViewForm
     */
    protected function getRoleViewFormElement()
    {
        return $this->elementFactory->createElement('UserRoleView');
    }

    /**
     * @return UserRoleForm
     */
    protected function getRoleEditFormElement()
    {
        return $this->elementFactory->createElement('UserRoleForm');
    }
}
