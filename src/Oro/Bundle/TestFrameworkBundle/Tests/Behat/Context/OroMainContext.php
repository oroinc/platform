<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
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
    use KernelDictionary, WaitingDictionary, ElementFactoryDictionary;

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
        $url = $this->getSession()->getCurrentUrl();

        if (1 === preg_match('/^[\S]*\/user\/login\/?$/i', $url)) {
            $this->waitPageToLoad();

            return;
        } elseif ('about:blank' === $url) {
            return;
        }

        $this->waitForAjax();
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
     */
    public function iFillFormWith($formName, TableNode $table)
    {
        $this->createElement($formName)->fill($table);
    }

    /**
     * @Given /^(?:|I )open the menu "(?P<path>(?:[^"]|\\")*)" (and|then) click "(?P<linkLocator>(?:[^"]|\\")*)"$/
     */
    public function iOpenTheMenuAndClick($path, $linkLocator)
    {
        $this->createElement('MainMenu')->openAndClick($path, $linkLocator);
    }

    /**
     * @Given press select arrow in :locator field
     */
    public function pressSelectArrowInOwnerField($locator)
    {
        $field = $this->createElement('OroForm')->findField($locator);
        $arrow = $field->getParent()->find('css', '.select2-arrow');
        $arrow->click();
    }

    /**
     * @Given press select entity button on :field field
     */
    public function pressSelectEntityButton($field)
    {
        $this->createOroForm()->pressEntitySelectEntityButton($field);
    }

    /**
     * @Given fill :text in search entity field
     */
    public function fillSelect2Search($text)
    {
        $this->createOroForm()->fillSelect2Search($text);
    }

    /**
     * Check count entities in search result of select2 entity field
     * @Given /^(?:|I )must see (?:|only )(?P<resultCount>(?:|one|two|\d+)) result$/
     */
    public function mustSeeCountOfResult($resultCount)
    {
        expect($this->createOroForm()->getSelect2ResultsCount())
            ->toBe($this->getCount($resultCount));
    }

    /**
     * Press on record in search results of select2-results field
     *
     * @Then /^(?:|I )press on "(?P<text>[\w\s]+)" in search result$/
     */
    public function iPressTextInSearchResults($text)
    {
        $this->createOroForm()->pressTextInSearchResult($text);
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
        $this->createElement('OroForm')->selectFieldOption($select, $option);
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
