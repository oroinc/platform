<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\UserBundle\Entity\User;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectAware;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements
    Context,
    SnippetAcceptingContext,
    PageObjectAware,
    KernelAwareContext
{
    use KernelDictionary;

    /** @var  \SensioLabs\Behat\PageObjectExtension\PageObject\Factory */
    protected $pageObjectFactory;

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
        $this->iWaitingForAjaxResponce();
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * @AfterScenario
     */
    public function afterScenario(AfterScenarioScope $scope)
    {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $screenshot = sprintf(
            '%s/%s.png',
            $this->getKernel()->getLogDir(),
            $scope->getScenario()->getTitle()
        );
        file_put_contents($screenshot, $this->getSession()->getScreenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function setPageObjectFactory(PageObjectFactory $pageObjectFactory)
    {
        $this->pageObjectFactory = $pageObjectFactory;
    }

    /**
     * @Then I should see :title flash message
     */
    public function iShouldSeeFlashMessage($title)
    {
        $this->assertSession()->elementTextContains('css', '.flash-messages-holder', $title);
    }

    /**
     * @Given /^user exists with:$/
     */
    public function userExists(TableNode $data)
    {
        $this->getKernel()->boot();
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager = $this->getContainer()->get('oro_user.manager');

        $user = $manager->createUser();

        foreach ($data as $row) {
            switch ($row['field']) {
                case 'roles':
                    $roles = explode(',', $row['value']);
                    array_walk($roles, function ($role) use ($user, $em) {
                        $roleEntity = $em->getRepository('OroUserBundle:Role')->findOneBy(['label' => trim($role)]);
                        $user->addRole($roleEntity);
                    });
                    break;
                case 'password':
                    $user->setPlainPassword($row['value']);
                    break;
                default:
                    $user->{'set'.ucfirst($row['field'])}($row['value']);
            }
        }

        $organization = $em
            ->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        $businessUnit = $em
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy([]);

        $user
            ->setEnabled(true)
            ->addBusinessUnit($businessUnit)
            ->addOrganization($organization)
            ->setOrganization($organization)
        ;

        $manager->updateUser($user);
        $em->flush();
    }

    /**
     * @Given Login as an existing :login user and :password password
     */
    public function loginAsAnExistingUserAndPassword($login, $password)
    {
        $this->visit('user/login');
        $this->fillField('_username', $login);
        $this->fillField('_password', $password);
        $this->pressButton('_submit');
        $errorBlock = $this->getSession()->getPage()->find('css', '.alert-error');
    }

    /**
     * {@inheritdoc}
     */
    public function pressButton($button)
    {
        try {
            parent::pressButton($button);
            $this->iWaitingForAjaxResponce();
        } catch (ElementNotFoundException $e) {
            if ($this->getSession()->getPage()->hasLink($button)) {
                $this->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Wait for AJAX to finish.
     *
     * @Given /^(?:|I )waiting for AJAX responce$/
     * @param int $time Time should be in milliseconds
     */
    public function iWaitingForAjaxResponce($time = 15000)
    {
        $this->getSession()->wait(
            $time,
            '(typeof($) != "undefined" '.
            '&& document.title !=="Loading..." '.
            '&& $ !== null '.
            '&& false === $( "div.loader-mask" ).hasClass("shown")) '.
            '&& "complete" == document["readyState"]'
        );
    }

    /**
     * @Given /^(?:|I )fill "(?P<element>(?:[^"]|\\")*)" with:$/
     */
    public function iFillWith($element, TableNode $table)
    {
        $this->pageObjectFactory->createElement($element)->fill($table);
    }
}
