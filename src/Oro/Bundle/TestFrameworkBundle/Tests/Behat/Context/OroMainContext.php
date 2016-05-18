<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroElementFactoryAware,
    KernelAwareContext
{
    use KernelDictionary;

    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
        $this->iWaitingForAjaxResponse();
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
     * @param OroElementFactory $elementFactory
     *
     * @return null
     */
    public function setElementFactory(OroElementFactory $elementFactory)
    {
        $this->elementFactory = $elementFactory;
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
        $user->setPlainPassword('test');

        foreach ($data->getRows() as $row) {
            switch ($row[0]) {
                case 'roles':
                    $roles = explode(',', $row[1]);
                    array_walk($roles, function ($role) use ($user, $em) {
                        $roleEntity = $em->getRepository('OroUserBundle:Role')->findOneBy(['label' => trim($role)]);
                        $user->addRole($roleEntity);
                    });
                    break;
                default:
                    $user->{'set'.ucfirst($row[0])}($row[1]);
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
    }

    /**
     * @Given Login as an existing :login user and :password password
     * @Given Login as :login
     */
    public function loginAsAnExistingUserAndPassword($login, $password)
    {
        $this->visit('user/login');
        $this->fillField('_username', $login);
        $this->fillField('_password', $password);
        $this->pressButton('_submit');
    }

    /**
     * {@inheritdoc}
     */
    public function pressButton($button)
    {
        try {
            parent::pressButton($button);
            $this->iWaitingForAjaxResponse();
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
     * @Given /^(?:|I )waiting for AJAX response$/
     * @param int $time Time should be in milliseconds
     */
    public function iWaitingForAjaxResponse($time = 15000)
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
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" form with:$/
     */
    public function iFillFormWith($formName, TableNode $table)
    {
        $this->elementFactory->createElement($formName)->fill($table);
    }
}
