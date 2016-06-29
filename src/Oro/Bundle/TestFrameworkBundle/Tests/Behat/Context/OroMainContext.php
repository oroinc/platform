<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroElementFactoryAware,
    KernelAwareContext
{
    use KernelDictionary, ElementFactoryDictionary;

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
        $url = $this->getSession()->getCurrentUrl();

        if (1 === preg_match('/^[\S]*\/user\/login\/?$/i', $url)) {
            $this->getSession()->getDriver()->waitPageToLoad();

            return;
        } elseif (0 === preg_match('/^https?:\/\//', $url)) {
            return;
        }

        $this->getSession()->getDriver()->waitForAjax();
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
            '%s/%s-%s-line.png',
            $this->getKernel()->getLogDir(),
            $scope->getFeature()->getTitle(),
            $scope->getScenario()->getLine()
        );

        file_put_contents($screenshot, $this->getSession()->getScreenshot());
    }

    /**
     * @Then /^(?:|I should )see "(?P<title>[^"]+)" flash message$/
     */
    public function iShouldSeeFlashMessage($title)
    {
        $this->assertSession()->elementTextContains('css', '.flash-messages-holder', $title);
    }

    /**
     * @Given /^(?:|I )login as "(?P<login>(?:[^"]|\\")*)" user with "(?P<password>(?:[^"]|\\")*)" password$/
     */
    public function loginAsUserWithPassword($login, $password)
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
        } catch (ElementNotFoundException $e) {
            if ($this->getSession()->getPage()->hasLink($button)) {
                $this->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" form with:$/
     * @When /^(?:|I )fill form with:$/
     */
    public function iFillFormWith(TableNode $table, $formName = "OroForm")
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $form->fill($table);
    }

    /**
     * Navigate through menu navigation
     * Every menu link must be separated by slash symbol "/"
     * Example: Given I go to System/ Channels
     * Example: And go to System/ User Management/ Users
     * @Given /^(?:|I )go to (?P<path>[^"]*)$/
     */
    public function iOpenTheMenuAndClick($path)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick($path);
    }

    /**
     * @Given press select entity button on :field field
     */
    public function pressSelectEntityButton($field)
    {
        $this->createOroForm()->pressEntitySelectEntityButton($field);
    }

    /**
     * @When /^(?:|I )save and close form$/
     */
    public function iSaveAndCloseForm()
    {
        $this->createOroForm()->saveAndClose();
    }

    /**
     * @When updated date must be grater then created date
     */
    public function updatedDateMustBeGraterThenCreatedDate()
    {
        /** @var NodeElement[] $records */
        $records = $this->getSession()->getPage()->findAll('css', 'div.navigation div.customer-content ul li');
        $createdDate = new \DateTime(
            str_replace('Created At: ', '', $records[0]->getText())
        );
        $updatedDate = new \DateTime(
            str_replace('Updated At: ', '', $records[1]->getText())
        );

        expect($updatedDate > $createdDate)->toBe(true);
    }

    /**
     * @When /^([\w\s]*) should be an owner$/
     */
    public function userShouldBeAnOwner($owner)
    {
        expect($this->getSession()->getPage()->find('css', '.user-info-state li a')->getText())
            ->toBe($owner);
    }

    /**
     * @When /^([\w\s]*) field should have ([\w\s]*) value$/
     */
    public function fieldShouldHaveValue($fieldName, $fieldValue)
    {
        file_put_contents('/tmp/test.html', $this->getSession()->getPage()->getHtml());
        $page = $this->getSession()->getPage();
        $labels = $page->findAll('css', 'label');

        /** @var NodeElement $label */
        foreach ($labels as $label) {
            if (preg_match(sprintf('/%s/i', $fieldName), $label->getText())) {
                $value = $label->getParent()->find('css', 'div.control-label')->getText();
                expect($value)->toMatch(sprintf('/%s/i', $fieldValue));

                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find field with "%s" label', $fieldName),
            $this->getSession()->getDriver()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($select, $option)
    {
        $select = $this->fixStepArgument($select);
        $option = $this->fixStepArgument($option);
        $this->createOroForm()->selectFieldOption($select, $option);
    }

    /**.
     * @return OroForm
     */
    protected function createOroForm()
    {
        return $this->createElement('OroForm');
    }

    /**
     * @param int|string $count
     * @return int
     */
    protected function getCount($count)
    {
        switch (trim($count)) {
            case '':
                return 1;
            case 'one':
                return 1;
            case 'two':
                return 2;
            default:
                return (int) $count;
        }
    }
}
