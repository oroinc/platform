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
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
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
     * @Then /^(?:|I )click update schema$/
     */
    public function iClickUpdateSchema()
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $page = $this->getSession()->getPage();

        $page->clickLink('Update schema');
        $driver->waitForAjax();
        $page->clickLink('Yes, Proceed');
        $driver->waitForAjax(120000);
    }

    /**
     * Search text in current collapsed activity
     *
     * @Then /^(?:|I )should see (?P<text>.+) text in activity/
     */
    public function iShouldSeeTextInCollapsedActivityItem($text)
    {
        if (false === strpos($this->getCollapsedItem()->getText(), $text)) {
            throw new ExpectationException(
                sprintf('Can\'t find "%s" image name in collapsed activity item', $text),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * Assert form error message
     * Example: Then I should see "At least one of the fields First name, Last name must be defined." error message
     *
     * @Then /^(?:|I should )see "(?P<title>[^"]+)" error message$/
     */
    public function iShouldSeeErrorMessage($title)
    {
        $this->assertSession()->elementTextContains('css', '.alert-error', $title);
    }

    /**
     * This is available for collection fields
     * See Emails and Phones in Contact create page
     * Example: And set "charlie@gmail.com" as primary email
     * Example: And set "+1 415-731-9375" as primary phone
     *
     * @Given /^(?:|I )set "(?P<value>[^"]+)" as primary (?P<field>[^"]+)$/
     */
    public function setFieldWithValueAsPrimary($field, $value)
    {
        /** @var CollectionField $collection */
        $collection = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($field)));
        $collection->setFieldAsPrimary($value);
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
     * Fill embed form
     * Example: And I fill in address:
     *            | Primary         | check         |
     *            | Country         | United States |
     *            | Street          | Selma Ave     |
     *            | City            | Los Angeles   |
     *
     * @Given /^(?:|I )fill in (?P<fieldSetLabel>[^"]+):$/
     */
    public function iFillInFieldSet($fieldSetLabel, TableNode $table)
    {
        /** @var Form $fieldSet */
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $fieldSet->fill($table);
    }

    /**
     * Set collection field with set of values
     * Example: And set Reminders with:
     *            | Method        | Interval unit | Interval number |
     *            | Email         | days          | 1               |
     *            | Flash message | minutes       | 30              |
     *
     * @Given /^(?:|I )set (?P<field>[^"]+) with:$/
     */
    public function setCollectionFieldWith($field, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');
        $form->fillField($field, $table);
    }

    /**
     * Add new embed form with data
     * Example: And add new address with:
     *            | Primary         | check               |
     *            | Country         | Ukraine             |
     *            | Street          | Myronosytska 57     |
     *            | City            | Kharkiv             |
     *            | Zip/postal code | 61000               |
     *            | State           | Kharkivs'ka Oblast' |
     *
     * @Given /^(?:|I )add new (?P<fieldSetLabel>[^"]+) with:$/
     */
    public function addNewAddressWith($fieldSetLabel, TableNode $table)
    {
        /** @var Form $fieldSet */
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $fieldSet->clickLink('Add');
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $form = $fieldSet->getLastSet();
        $form->fill($table);
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
     * Example: Given I click My Emails in user menu
     *
     * @Given /^(?:|I )click (?P<needle>[\w\s]+) in user menu$/
     */
    public function iClickLinkInUserMenu($needle)
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', 'i.icon-sort-down')->click();
        $links = $userMenu->findAll('css', 'ul.dropdown-menu li a');

        /** @var NodeElement $link */
        foreach ($links as $link) {
            if (preg_match(sprintf('/%s/i', $needle), $link->getText())) {
                $link->click();

                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find "%s" item in user menu', $needle),
            $this->getSession()->getDriver()
        );
    }

    /**
     * Select context entity in context selector in popup after "Add context" button is pressed
     * Example: And select User in activity context selector
     *
     * @Given /^(?:|I )select (?P<needle>[\w\s]+) in activity context selector$/
     */
    public function selectUserInActivityContextSelector($needle)
    {
        $contextSelector = $this->elementFactory->createElement('ContextSelector');
        $contextSelector->find('css', 'span.icon-caret-down')->click();
        $contexts = $contextSelector->findAll('css', 'ul.context-items-dropdown li');

        /** @var NodeElement $context */
        foreach ($contexts as $context) {
            if ($needle === $context->getText()) {
                $context->click();
                $this->getSession()->getDriver()->waitForAjax();

                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find "%s" context in context selector', $needle),
            $this->getSession()->getDriver()
        );
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
     * Navigate through menu navigation
     * Every menu link must be separated by slash symbol "/"
     * Example: Given I go to System/ Channels
     * Example: And go to System/ User Management/ Users
     *
     * @Given /^(?:|I )go to (?P<path>[^"]*)$/
     */
    public function iOpenTheMenuAndClick($path)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick($path);
    }

    /**
     * Delete all context from active (collapsed) item in activity list
     * Example: And delete all contexts from collapsed email
     *
     * @When /^(?:|I )delete all contexts from collapsed ([\w\s]*)$/
     */
    public function deleteAllContextsFromActionItem()
    {
        $collapsedItem = $this->getCollapsedItem();
        $contexts = $collapsedItem->findAll('css', 'div.activity-context-activity-list div.context-item');

        /** @var NodeElement $context */
        foreach ($contexts as $context) {
            $context->find('css', 'i.icon-remove')->click();
        }
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
     * Assert that activity item with given text is present in activity list
     * Example: And should see "Fwd: Re: Work for you" email in activity list
     *
     * @Then /^(?:|I )should see "(?P<content>[^"]*)" ([\w\s]*) in activity list$/
     */
    public function shouldSeeRecordInActivityList($content)
    {
        $this->getActivityListItem($content);
    }

    /**
     * Assert that activity item with given text is NOT present in activity list
     * Example: And shouldn't see "Fwd: Re: Work for you" email in activity list
     *
     * @Then /^(?:|I )shouldn't see "(?P<content>[^"]*)" ([\w\s]*) in activity list$/
     */
    public function shouldNotSeeRecordInActivityList($content)
    {
        try {
            $this->getActivityListItem($content);
        } catch (ExpectationException $e) {
            return;
        }

        throw new ExpectationException(
            sprintf('Not expect to find "%s" activity item, but was found', $content),
            $this->getSession()->getDriver()
        );
    }

    /**
     * @Then there is no records in activity list
     */
    public function thereIsNoRecordsInActivityList()
    {
        $itemsCount = count($this->getActivityListItems());

        if (0 !== $itemsCount) {
            throw new ExpectationException(
                sprintf('Expect that Activity list not found items, but found %s', $itemsCount),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * Find activity item in activity list and collapse it
     * Example: When I collapse "Fwd: Re: Work for you" in activity list
     *
     * @When /^(?:|I )collapse "(?P<content>[^"]*)" in activity list$/
     */
    public function iCollapseActivityListItem($content)
    {
        $item = $this->getActivityListItem($content);
        $item->find('css', 'a.accordion-toggle')->click();
        $this->getSession()->getDriver()->waitForAjax();
    }

    /**
     * Click action link on activity item
     * Example: And I click "Forward" on "Work for you" in activity list
     * Example: And I click "Reply" on "Work for you" in activity list
     *
     * @Given /^(?:|I )click "(?P<action>[\w\s]*)" on "(?P<content>[\w\s]*)" in activity list$/
     */
    public function iClickActionOnContentInActivityList($action, $content)
    {
        $item = $this->getActivityListItem($content);

        $item->find('css', 'div.actions a.dropdown-toggle')->mouseOver();
        $links = $item->findAll('css', 'li.launcher-item a');

        /** @var NodeElement $link */
        foreach ($links as $link) {
            if (preg_match(sprintf('/%s/i', $action), $link->getText())) {
                $link->click();

                return;
            }
        }

        throw new ExpectationException(
            sprintf('"%s" activity item was found, but "%s" action not', $content, $action),
            $this->getSession()->getDriver()
        );
    }

    /**
     * Assert that email body in activity list has substring
     * Example: Then I should see "We have new role for you" in email body
     *
     * @Then /^(?:|I )should see "(?P<content>(?:[^"]|\\")*)" in email body$/
     */
    public function iShouldSeeInEmailBody($content)
    {
        $collapsedItem = $this->getCollapsedItem();
        $emailBody = $collapsedItem->find('css', 'div.email-body')->getHtml();

        if (false === strpos($emailBody, $content)) {
            throw new ExpectationException(
                sprintf('"%s" not found in "%s"', $content, $emailBody),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * @Then email :arg1 should have thread icon
     */
    public function emailShouldHaveThreadIcon($content)
    {
        $item = $this->getActivityListItem($content);
        $icon = $item->find('css', 'div.icon i');

        if (false === $icon->hasClass('icon-email-thread')) {
            throw new ExpectationException(
                sprintf('Expect that "%s" has thread email icon, but it hasn\'t', $content),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * Assert count of emails in thread
     * Example: And email thread "Work for you" should have two emails
     *
     * @Then /^email thread "(?P<content>(?:[^"]|\\")*)" should have (?P<emailsCount>(?:|one|two|\d+)) emails$/
     */
    public function emailShouldHaveTwoEmails($content, $emailsCount)
    {
        $item = $this->getActivityListItem($content);
        $threadEmails = $item->findAll('css', 'div.thread-view div.email-info');

        if ($this->getCount($emailsCount) !== count($threadEmails)) {
            throw new ExpectationException(
                sprintf('Expect %s number of emails in thread, but get %s', $emailsCount, count($threadEmails)),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * Assert that one of contexts contains text
     *
     * @Then /^(?:|I )should see (?P<text>\w+) in Contexts$/
     */
    public function iShouldSeeNameInContexts($text)
    {
        $collapsedItem = $this->getCollapsedItem();
        $contexts = $collapsedItem->findAll('css', 'div.activity-context-activity-list div.context-item a');

        /** @var NodeElement $context */
        foreach ($contexts as $context) {
            if (false !== strpos($context->getText(), $text)) {
                return;
            }
        }

        throw new ExpectationException(
            sprintf('Context with "%s" name not found', $text),
            $this->getSession()->getDriver()
        );
    }

    /**
     * Assert text by label in page
     *
     * @Then /^(?:|I )should see (?P<entity>[\w\s]+) with:$/
     */
    public function assertValuesByLabels($entity, TableNode $table)
    {
        $page = $this->getSession()->getPage();

        foreach ($table->getRows() as $row) {
            $label = $page->find('xpath', sprintf('//label[text()="%s"]', $row[0]));

            if (!$label) {
                throw new ExpectationException(
                    sprintf('Can\'t find "%s" label', $row[0]),
                    $this->getSession()->getDriver()
                );
            }

            $text = $label->getParent()->find('css', 'div.controls div.control-label')->getText();

            if (false === preg_match(sprintf('/%s/i', $row[1]), $text)) {
                throw new ExpectationException(
                    sprintf('Expect "%s" text of "%s" label, but got "%s"', $row[1], $row[0], $text),
                    $this->getSession()->getDriver()
                );
            }
        }
    }

    /**
     * @param string $content
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getActivityListItem($content)
    {
        foreach ($this->getActivityListItems() as $item) {
            if (false !== strpos($item->getText(), $content)) {
                return $item;
            }
        }

        throw new ExpectationException(
            sprintf('Item with "%s" content not found in activity list', $content),
            $this->getSession()->getDriver()
        );
    }

    /**
     * @return NodeElement[]
     * @throws ExpectationException
     */
    protected function getActivityListItems()
    {
        $page = $this->getSession()->getPage();
        $sections = $page->findAll('css', 'h4.scrollspy-title');

        /** @var NodeElement $section */
        foreach ($sections as $section) {
            if ('Activity' === $section->getText()) {
                return $section->getParent()->findAll('css', 'div.list-item');
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find Activity section on page'),
            $this->getSession()->getDriver()
        );
    }

    /**
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getCollapsedItem()
    {
        $items = $this->getSession()->getPage()->findAll('css', 'div.accordion-body');
        $collapsedItem = array_filter($items, function (NodeElement $element) {
            return $element->hasClass('in');
        });

        if (0 === count($collapsedItem)) {
            throw new ExpectationException(
                'Not found collapsed items in activity list',
                $this->getSession()->getDriver()
            );
        }

        return array_shift($collapsedItem);
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
