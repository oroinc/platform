<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Element\AttachmentItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserMenu;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroPageObjectAware,
    KernelAwareContext
{
    use AssertTrait, KernelDictionary, PageObjectDictionary;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * Example: Then I should see "Attachment created successfully" flash message
     * Example: Then I should see "The email was sent" flash message
     *
     * @Then /^(?:|I )should see "(?P<title>[^"]+)" flash message$/
     */
    public function iShouldSeeFlashMessage($title)
    {
        /** @var NodeElement|false $messageElement */
        $messageElement = $this->spin(function (OroMainContext $context) use ($title) {
            $flashMessage = $context->findElementContains('Flash Message', $title);

            if ($flashMessage->isValid() && $flashMessage->isVisible()) {
                return $flashMessage;
            }

            return false;
        });

        self::assertNotFalse($messageElement, 'Flash message not found on page');
        $flashMessage = $messageElement->getText();

        $closeButton = $messageElement->find('css', 'button.close');

        if ($closeButton->isVisible()) {
            $closeButton->press();
        }

        self::assertContains($title, $flashMessage, sprintf(
            'Expect that "%s" flash message contains "%s" string, but it isn\'t',
            $flashMessage,
            $title
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsText($text)
    {
        $result = $this->spin(function (OroMainContext $context) use ($text) {
            $context->assertSession()->pageTextContains($this->fixStepArgument($text));

            return true;
        });

        self::assertTrue(
            $result,
            sprintf('The text "%s" was not found anywhere in the text of the current page.', $text)
        );
    }

    /**
     * Assert form error message
     * Example: Then I should see "At least one of the fields First name, Last name must be defined." error message
     *
     * @Then /^(?:|I should )see "(?P<title>[^"]+)" error message$/
     */
    public function iShouldSeeErrorMessage($title)
    {
        $errorElement = $this->spin(function (MinkContext $context) {
            return $context->getSession()->getPage()->find('css', '.alert-error');
        });

        self::assertNotFalse($errorElement, 'Error message not found on page');
        $message = $errorElement->getText();
        $errorElement->find('css', 'button.close')->press();

        self::assertContains($title, $message, sprintf(
            'Expect that "%s" error message contains "%s" string, but it isn\'t',
            $message,
            $title
        ));
    }

    /**
     * @param \Closure $lambda
     * @param int $timeLimit in seconds
     * @return null|mixed Return null if closure throw error or return not true value.
     *                     Return value that return closure
     */
    public function spin(\Closure $lambda, $timeLimit = 60)
    {
        $time = $timeLimit;

        while ($time > 0) {
            try {
                if ($result = $lambda($this)) {
                    return $result;
                }
            } catch (\Exception $e) {
                // do nothing
            }

            usleep(250000);
            $time -= 0.25;
        }

        return null;
    }

    /**
     * Assert that page hase h1 header
     * Example: And page has "My own custom dashboard" header
     * Example: Then page has "Dashboard" header
     *
     * @Then page has :header header
     */
    public function pageHasHeader($header)
    {
        $this->assertSession()->elementTextContains('css', 'div#container h1', $header);
    }

    /**
     * Close form error message
     *
     * @Then /^(?:|I )close error message$/
     */
    public function closeErrorMessage()
    {
        $this->createOroForm()->find('css', '.alert-error button.close')->press();
    }

    /**
     * Close UI dialog popup
     *
     * @Then /^(?:|I )close ui dialog$/
     */
    public function closeUiDialog()
    {
        $this->getSession()->getPage()->find('css', 'button.ui-dialog-titlebar-close')->press();
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
     * Fill form with data
     * Example: And fill form with:
     *            | Subject     | Simple text     |
     *            | Users       | [Charlie, Pitt] |
     *            | Date        | 2017-08-24      |
     *
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" with:$/
     * @When /^(?:|I )fill form with:$/
     */
    public function iFillFormWith(TableNode $table, $formName = "OroForm")
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $form->fill($table);
    }

    /**
     * Assert form fields values
     * Example: And "User" form must contains values:
     *            | Username          | charlie           |
     *            | First Name        | Charlie           |
     *            | Last Name         | Sheen             |
     *            | Primary Email     | charlie@sheen.com |
     *
     * @Then /^"(?P<formName>(?:[^"]|\\")*)" must contains values:$/
     */
    public function formMustContainsValues($formName, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $form->assertFields($table);
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
     *            | Zip/Postal Code | 61000               |
     *            | State           | Kharkivs'ka Oblast' |
     *
     * @Given /^(?:|I )add new (?P<fieldSetLabel>[^"]+) with:$/
     */
    public function addNewFieldSetWith($fieldSetLabel, TableNode $table)
    {
        /** @var Form $fieldSet */
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $fieldSet->clickLink('Add');
        $this->waitForAjax();
        $form = $fieldSet->getLastSet();
        $form->fill($table);
    }

    /**
     * Open dashboard login page and login as existing user
     * Demo user should have password the same as username, e.g. username: charlie, password: charlie
     * Example: Given I login as administrator
     * Example: Given I login as "charlie" user
     *
     * @Given /^(?:|I )login as "(?P<loginAndPassword>(?:[^"]|\\")*)" user$/
     * @Given /^(?:|I )login to dashboard as "(?P<loginAndPassword>(?:[^"]|\\")*)" user$/
     * @Given /^(?:|I )login as administrator$/
     * @Given /^(?:|I )login to dashboard as administrator$/
     */
    public function loginAsUserWithPassword($loginAndPassword = 'admin')
    {
        $router = $this->getContainer()->get('router');

        $this->visit($router->generate('oro_user_security_logout'));
        $this->visit($router->generate('oro_default'));
        $this->fillField('_username', $loginAndPassword);
        $this->fillField('_password', $loginAndPassword);
        $this->pressButton('_submit');
    }

    /**
     * Example: Given I click My Emails in user menu
     * Example: Given I click My Calendar in user menu
     *
     * @Given /^(?:|I )click (?P<needle>[\w\s]+) in user menu$/
     */
    public function iClickLinkInUserMenu($needle)
    {
        /** @var UserMenu $userMenu */
        $userMenu = $this->createElement('UserMenu');
        self::assertTrue($userMenu->isValid());
        $userMenu->open();
        $userMenu->clickLink($needle);
    }

    /**
     * Click on element on page
     * Example: When I click on "Help Icon"
     *
     * @When /^(?:|I )click on "(?P<element>[\w\s]+)"$/
     */
    public function iClickOn($element)
    {
        $this->createElement($element)->click();
    }

    /**
     * Assert popup with large image on page
     *
     * @Then /^(?:|I )should see large image$/
     */
    public function iShouldSeeLargeImage()
    {
        $largeImage = $this->getSession()->getPage()->find('css', '.lg-image');
        self::assertNotNull($largeImage, 'Large image not visible');
    }

    /**
     * @Then /^(?:|I )close large image preview$/
     */
    public function closeLargeImagePreview()
    {
        $page = $this->getSession()->getPage();
        $page->find('css', '.lg-image')->mouseOver();
        $page->find('css', 'span.lg-close')->click();
    }

    /**
     * Example: When I click on "cat.jpg" attachment thumbnail
     * Example: And I click on "note-attachment.jpg" attachment thumbnail
     *
     * @Then /^(?:|I )click on "(?P<text>[^"]+)" attachment thumbnail$/
     */
    public function commentAttachmentShouldProperlyWork($text)
    {
        /** @var AttachmentItem $attachmentItem */
        $attachmentItem = $this->elementFactory->findElementContains('AttachmentItem', $text);
        self::assertTrue($attachmentItem->isValid(), sprintf('Attachment with "%s" text not found', $text));

        $attachmentItem->clickOnAttachmentThumbnail();

        $thumbnail = $this->getPage()->find('css', "div.thumbnail a[title='$text']");
        self::assertTrue($thumbnail->isValid(), sprintf('Thumbnail "%s" not found', $text));

        $thumbnail->click();
    }

    /**
     * Assert that download link in attachment works properly
     * Example: And download link for "cat.jpg" attachment should work
     * Example: And download link for "note-attachment.jpg" attachment should work
     *
     * @Then /^download link for "(?P<text>[^"]+)" attachment should work$/
     */
    public function downloadLinkForAttachmentShouldWork($text)
    {
        /** @var AttachmentItem $attachmentItem */
        $attachmentItem = $this->elementFactory->findElementContains('AttachmentItem', $text);
        self::assertTrue($attachmentItem->isValid(), sprintf('Attachment with "%s" text not found', $text));

        $attachmentItem->checkDownloadLink();
    }

     /**
      * Click on button or link
      * Example: Given I click "Edit"
      * Example: When I click "Save and Close"
      *
      * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)"$/
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
     * @Given /^(?:|I )go to (?P<path>(?:(?!([nN]ewer|[oO]lder) activities)(?!.*page)([^"]*)))$/
     */
    public function iOpenTheMenuAndClick($path)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick($path);
    }

    /**
     * Assert current page
     * Example: Then I should be on Search Result page
     * Example: Then I should be on Default Calendar View page
     *
     * @Given /^(?:|I )should be on (?P<page>[\w\s\/]+) page$/
     */
    public function assertPage($page)
    {
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals($this->getPage($page)->getRoute(), $route['_route']);
    }

    /**
     * Example: Given I open Opportunity Create page
     * Example: Given I open Account Index page
     *
     * @Given /^(?:|I )open (?P<pageName>[\w\s\/]+) page$/
     */
    public function openPage($pageName)
    {
        $this->getPage($pageName)->open();
    }

    /**
     * Example: Given I open "Charlie" Account edit page
     * Example: When I open "Supper sale" opportunity edit page
     *
     * @Given /^(?:|I )open "(?P<title>[\w\s]+)" (?P<entity>[\w\s]+) edit page$/
     */
    public function openEntityEditPage($title, $entity)
    {
        $pageName = preg_replace('/\s+/', ' ', ucwords($entity)).' Edit';
        $this->getPage($pageName)->open(['title' => $title]);
    }

    /**
     * Example: And press select entity button on Owner field
     *
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
     * @When /^(?:|I )(save|submit) form$/
     */
    public function iSaveForm()
    {
        $this->createOroForm()->save();
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

        self::assertGreaterThan($updatedDate, $createdDate);
    }

    /**
     * Assert entity owner
     * Example: And Harry Freeman should be an owner
     * Example: And Todd Greene should be an owner
     *
     * @When /^([\w\s]*) should be an owner$/
     */
    public function userShouldBeAnOwner($owner)
    {
        self::assertEquals(
            $owner,
            $this->getSession()->getPage()->find('css', '.user-info-state li a')->getText()
        );
    }

    /**
     * Find and assert field value
     * It's valid for entity edit or entity view page
     * Example: And Account Name field should has Good Company value
     * Example: And Account Name field should has Good Company value
     * Example: And Description field should has Our new partner value
     *
     * @When /^(?P<fieldName>[\w\s]*) field should has (?P<fieldValue>.+) value$/
     */
    public function fieldShouldHaveValue($fieldName, $fieldValue)
    {
        $page = $this->getSession()->getPage();
        $labels = $page->findAll('css', 'label');

        /** @var NodeElement $label */
        foreach ($labels as $label) {
            if (preg_match(sprintf('/%s/i', $fieldName), $label->getText())) {
                if ($label->hasAttribute('for')) {
                    return $this->getSession()
                        ->getPage()
                        ->find('css', '#'.$label->getAttribute('for'))
                        ->getValue();
                }

                $value = $label->getParent()->find('css', 'div.control-label')->getText();
                self::assertRegExp(sprintf('/%s/i', $fieldValue), $value);

                return;
            }
        }

        self::fail(sprintf('Can\'t find field with "%s" label', $fieldName));
    }

    /**
     * Inline edit field
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>.+) as "(?P<value>.*)"$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)"$/
     */
    public function inlineEditField($field, $value, $entityTitle = null)
    {
        /** @var Grid $grid */
        $grid = $this->createElement('Grid');

        if (null === $entityTitle) {
            $row = $grid->getRowByContent($entityTitle);
        } else {
            $rows = $grid->getRows();
            self::assertCount(1, $rows, sprintf('Expect one row in grid but got %s.'.
                PHP_EOL.'You can specify row content for edit field in specific row.'));

            $row = array_shift($rows);
        }

        $row->setCellValue($field, $value);
        $this->iShouldSeeFlashMessage('Inline edits are being saved');
        $this->iShouldSeeFlashMessage('Record has been succesfully updated');
    }

    /**
     * Assert text by label in page.
     * Example: Then I should see call with:
     *            | Subject             | Proposed Charlie to star in new film |
     *            | Additional comments | Charlie was in a good mood           |
     *            | Call date & time    | Aug 24, 2017, 11:00 AM               |
     *            | Phone number        | (310) 475-0859                       |
     *            | Direction           | Outgoing                             |
     *            | Duration            | 5:30                                 |
     *
     * @Then /^(?:|I )should see (?P<entity>[\w\s]+) with:$/
     */
    public function assertValuesByLabels($entity, TableNode $table)
    {
        $page = $this->getSession()->getPage();

        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            $labelElement = $this->findElementContains('Label', $label);
            $labels = $page->findAll('xpath', $labelElement->getXpath());

            self::assertNotCount(0, $labels, sprintf('Can\'t find "%s" label', $label));

            /** @var NodeElement $label */
            foreach ($labels as $labelElement) {
                $controlLabel = $labelElement->getParent()->find('css', 'div.controls div.control-label');
                self::assertNotNull($controlLabel);
                $text = $controlLabel->getText();

                if (false !== stripos($text, $value)) {
                    continue 2;
                }
            }

            self::fail(
                sprintf('Found %s "%s" labels, but no one has "%s" text value', count($labels), $label, $value)
            );
        }
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

    /**
     * {@inheritdoc}
     */
    public function fillField($field, $value)
    {
        $field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);
        $this->createOroForm()->fillField($field, $value);
    }

    /**
     * Assert that field is required
     * Example: Then Opportunity Name is a required field
     * Example: Then Opportunity Name is a required field
     *
     * @Then /^(?P<label>[\w\s]+) is a required field$/
     */
    public function fieldIsRequired($label)
    {
        $labelElement = $this->getPage()->findElementContains('Label', $label);
        self::assertTrue($labelElement->hasClass('required'));
    }

    /**
     * Type value in field chapter by chapter. Imitate real user input from keyboard
     * Example: And type "Common" in "search"
     * Example: When I type "Create" in "Enter shortcut action"
     *
     * @When /^(?:|I )type "(?P<value>(?:[^"]|\\")*)" in "(?P<field>(?:[^"]|\\")*)"$/
     */
    public function iTypeInFieldWith($locator, $value)
    {
        $locator = $this->fixStepArgument($locator);
        $value = $this->fixStepArgument($value);
        $field = $this->getPage()->find('named', array('field', $locator));
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        if (null === $field) {
            throw new ElementNotFoundException($driver, 'form field', 'id|name|label|value|placeholder', $locator);
        }

        self::assertTrue($field->isVisible(), "Field with '$locator' was found, but it not visible");

        $driver->typeIntoInput($field->getXpath(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementOnPage($element)
    {
        self::assertTrue(
            $this->createElement($element)->isVisible(),
            sprintf('Element "%s" is not visible, or not present on the page', $element)
        );
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

    /**
     * @param int $time
     */
    protected function waitForAjax($time = 60000)
    {
        return $this->getSession()->getDriver()->waitForAjax($time);
    }
}
