<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\AttachmentBundle\Tests\Behat\Element\AttachmentItem;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProviderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProviderAwareTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorInterface;
use Oro\Bundle\UIBundle\Tests\Behat\Element\ControlGroup;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserMenu;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroPageObjectAware,
    KernelAwareContext,
    SessionAliasProviderAwareInterface,
    MessageQueueIsolatorAwareInterface
{
    use AssertTrait, KernelDictionary, PageObjectDictionary, SessionAliasProviderAwareTrait;

    /**
     * @var MessageQueueIsolatorInterface
     */
    protected $messageQueueIsolator;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator)
    {
        $this->messageQueueIsolator = $messageQueueIsolator;
    }

    /**
     * @BeforeStep
     * @param BeforeStepScope $scope
     */
    public function beforeStep(BeforeStepScope $scope)
    {
        $this->messageQueueIsolator->waitWhileProcessingMessages(30);

        if (false === $this->getMink()->isSessionStarted('first_session')) {
            return;
        }

        $session = $this->getMink()->getSession('first_session');
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        $url = $session->getCurrentUrl();

        if (1 === preg_match('/^[\S]*\/user\/login\/?$/i', $url)) {
            $driver->waitPageToLoad();

            return;
        } elseif (0 === preg_match('/^https?:\/\//', $url)) {
            return;
        }

        // Don't wait when we need assert the flash message, because it can disappear until ajax in process
        if (preg_match('/^(?:|I )should see ".+"(?:| flash message| error message)$/', $scope->getStep()->getText())) {
            return;
        }

        $start = microtime(true);
        $result = $driver->waitForAjax();
        $timeElapsedSecs = microtime(true) - $start;

        if (!$result) {
            var_dump(sprintf(
                'Wait for ajax %d seconds, and it assume that ajax was NOT passed',
                $timeElapsedSecs
            ));
        }
    }

    /**
     * Example: Then I should see "Attachment created successfully" flash message
     * Example: Then I should see "The email was sent" flash message
     *
     * @Then /^(?:|I )should see "(?P<title>[^"]+)" flash message$/
     */
    public function iShouldSeeFlashMessage($title, $flashMessageElement = 'Flash Message', $timeLimit = 15)
    {
        $actualFlashMessages = [];
        /** @var Element|null $flashMessage */
        $flashMessage = $this->spin(
            function (OroMainContext $context) use ($title, &$actualFlashMessages, $flashMessageElement) {
                $flashMessages = $context->findAllElements($flashMessageElement);

                foreach ($flashMessages as $flashMessage) {
                    if ($flashMessage->isValid() && $flashMessage->isVisible()) {
                        $actualFlashMessageText = $flashMessage->getText();
                        $actualFlashMessages[$actualFlashMessageText] = $flashMessage;

                        if (false !== stripos($actualFlashMessageText, $title)) {
                            return $flashMessage;
                        }
                    }
                }

                return null;
            },
            $timeLimit
        );

        self::assertNotCount(0, $actualFlashMessages, 'No flash messages founded on page');
        self::assertNotNull($flashMessage, sprintf(
            'Expected "%s" message but got "%s" messages',
            $title,
            implode(',', array_keys($actualFlashMessages))
        ));

        try {
            /** @var NodeElement $closeButton */
            $closeButton = $flashMessage->find('css', 'button.close');
            $closeButton->press();
        } catch (\Throwable $e) {
            //No worries, flash message can disappeared till time next call
        } catch (\Exception $e) {
            //No worries, flash message can disappeared till time next call
        }
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
     * @param int $timeLimit
     * @return false|mixed Return false if closure throw error or return not true value.
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

        return false;
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
     * Assert that provided validation errors for given fields appeared
     * Example: Then I should see validation errors:
     *            | Subject         | This value should not be blank.  |
     *
     * @Then /^(?:|I )should see validation errors:$/
     */
    public function iShouldSeeValidationErrors(TableNode $table)
    {
        $form = $this->createOroForm();

        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            $error = $form->getFieldValidationErrors($label);
            self::assertEquals(
                $value,
                $error,
                "Failed asserting that $label has error $value"
            );
        }
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
     *
     * @param string $loginAndPassword
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
     * Login with credentials under specified session name and session alias
     * Registers an alias switches to specified session and performs the login procedure
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::switchToActorWindowSession
     *
     * @Given /^(?:|I )login as "(?P<credential>(?:[^"]|\\")*)" and use in (?P<session>\w+) as (?P<alias>\w+)$/
     * @Given /^(?:|I )login as administrator and use in "(?P<session>[^"]*)" as "(?P<alias>[^"]*)"$/
     *
     * @param string $session
     * @param string $alias
     * @param string $credential
     */
    public function loginAsUserWithPasswordInSession($session, $alias, $credential = 'admin')
    {
        $this->sessionAliasProvider->setSessionAlias($this->getMink(), $session, $alias);
        $this->sessionAliasProvider->switchSessionByAlias($this->getMink(), $alias);
        $this->loginAsUserWithPassword($credential);
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
     * Example: Given I click Websites in sidebar menu
     *
     * @Given /^(?:|I )click (?P<needle>[\w\s]+) in sidebar menu$/
     */
    public function iClickLinkInSidebarMenu($needle)
    {
        $sidebarMenu = $this->createElement('SidebarMenu');
        self::assertTrue($sidebarMenu->isValid());
        $sidebarMenu->clickLink($needle);
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
     * Example: Then I should see that "Header" contains "Some Text"
     * @Then /^I should see that "(?P<elementName>[^"]*)" contains "(?P<text>[^"]*)"$/
     *
     * @param string $elementName
     * @param string $text
     */
    public function assertDefinedElementContainsText($elementName, $text)
    {
        $this->waitForAjax();
        $element = $this->elementFactory->createElement($elementName);
        self::assertContains(
            $text,
            $element->getText(),
            sprintf('Element %s does not contains text %s', $elementName, $text)
        );
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
            } elseif ($this->elementFactory->hasElement($button)) {
                $this->elementFactory->createElement($button)->click();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Assert modal window with given caption is visible
     * Example: Then I should see "Changing Page URLs" modal window
     *
     * @Then /^(?:|I )should see "(?P<caption>(?:[^"]|\\")*)" modal window$/
     */
    public function iShouldSeeModalWindow($caption)
    {
        $modalWindow = $this->getSession()->getPage()->find('css', 'div.modal');
        self::assertTrue($modalWindow->isVisible(), 'There is no visible modal window on page at this moment');

        self::assertElementContainsText('div.modal .modal-header', $caption);
    }

    /**
     * Click on button in modal window
     * Example: Given I click "Edit" in modal window
     * Example: When I click "Save and Close" in modal window
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)" in modal window$/
     */
    public function pressButtonInModalWindow($button)
    {
        $modalWindow = $this->getSession()->getPage()->find('css', 'div.modal');
        self::assertTrue($modalWindow->isVisible(), 'There is no visible modal window on page at this moment');
        try {
            $button = $this->fixStepArgument($button);
            $modalWindow->pressButton($button);
        } catch (ElementNotFoundException $e) {
            if ($modalWindow->hasLink($button)) {
                $modalWindow->clickLink($button);
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
        $urlPath = preg_replace('/^.*\.php/', '', $urlPath);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals($this->getPage($page)->getRoute(), $route['_route']);
    }

    /**
     * Assert current page with its title
     *
     * @Given /^(?:|I )should be on "(?P<entityTitle>[\w\s\/]+)" (?P<page>[\w\s\/]+) ((v|V)iew) page$/
     */
    public function assertViewPage($page, $entityTitle)
    {
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals($this->getPage($page . ' View')->getRoute(), $route['_route']);

        $actualEntityTitle = $this->getSession()->getPage()->find('css', 'h1.user-name');
        self::assertNotNull($actualEntityTitle, sprintf('Entity title not found on "%s" view page', $page));
        self::assertEquals($entityTitle, $actualEntityTitle->getText());
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
        $pageName = preg_replace('/\s+/', ' ', ucwords($entity)) . ' Edit';
        $this->getPage($pageName)->open(['title' => $title]);
    }

    /**
     * Example: Given I open "Charlie" Account view page
     * Example: When I open "Supper sale" opportunity view page
     *
     * @Given /^(?:|I )open "(?P<title>[\w\s]+)" (?P<entity>[\w\s]+) view page$/
     */
    public function openEntityViewPage($title, $entity)
    {
        $pageName = preg_replace('/\s+/', ' ', ucwords($entity)) . ' View';
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
                        ->find('css', '#' . $label->getAttribute('for'))
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
     * Mass inline grid field edit
     * Accept table and pass it to inlineEditField
     * Example: When I edit first record from grid:
     *            | name      | editedName       |
     *            | status    | Qualified        |
     *
     * @Then I edit first record from grid:
     * @param TableNode $table
     */
    public function iEditFirstRecordFromGrid(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($field, $value) = $row;
            $this->inlineEditField($field, $value);
            $this->waitForAjax();
        }
    }

    /**
     * Inline grid field edit with click on empty space
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" with click on empty space$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" with click on empty space$/
     */
    public function inlineEditRecordInGridWithClickOnEmptySpace($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValue($field, $value);
        $this->clickOnEmptySpace();
        $this->iShouldSeeFlashMessage('Inline edits are being saved');
        $this->iShouldSeeFlashMessage('Record has been succesfully updated');
    }

    /**
     * Click on empty space
     * Example: When I click on empty space
     *
     * @When /^(?:|I )click on empty space$/
     */
    public function clickOnEmptySpace()
    {
        $this->getPage()->find('css', '#container')->click();
    }

    /**
     * Inline grid field edit by double click
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" by double click$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" by double click$/
     */
    public function inlineEditRecordInGridByDoubleclick($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueByDoubleClick($field, $value);
    }

    /**
     * @param string $entityTitle
     * @return \Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridRow
     */
    protected function getGridRow($entityTitle = null)
    {
        /** @var Grid $grid */
        $grid = $this->createElement('Grid');

        if (null !== $entityTitle) {
            $row = $grid->getRowByContent($entityTitle);
        } else {
            $rows = $grid->getRows();
            self::assertCount(1, $rows, sprintf('Expect one row in grid but got %s.' .
                PHP_EOL . 'You can specify row content for edit field in specific row.', count($rows)));

            $row = array_shift($rows);
        }

        return $row;
    }

    /**
     * Inline edit field
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)"$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)"$/
     */
    public function inlineEditField($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueAndSave($field, $value);
        $this->iShouldSeeFlashMessage('Inline edits are being saved');
        $this->iShouldSeeFlashMessage('Record has been succesfully updated');
    }

    /**
     * Inline edit field and don't save
     * Example: When I edit Status as "Open" without saving
     * Example: Given I edit Probability as "30" without saving
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" without saving$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" without saving$/
     */
    public function inlineEditFieldWithoutSaving($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValue($field, $value);
    }

    /**
     * Inline edit field and cancel
     * Example: When I edit Status as "Open" and cancel
     * Example: Given I edit Probability as "30" and cancel
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" and cancel$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" and cancel$/
     */
    public function inlineEditFieldAndCancel($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueAndCancel($field, $value);
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

            /** @var NodeElement $labelElement */
            foreach ($labels as $labelElement) {
                /** @var ControlGroup $controlLabel */
                $controlLabel = $this->elementFactory->wrapElement(
                    'ControlGroup',
                    $labelElement->getParent()->find('css', 'div.controls div.control-label')
                );

                if (true === $controlLabel->compareValues(Form::normalizeValue($value, $label))) {
                    continue 2;
                }
            }

            self::fail(
                sprintf('Found %s "%s" labels, but no one has "%s" value', count($labels), $label, $value)
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
        $field = $this->getPage()->find('named', ['field', $locator]);
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
        $isVisible = $this->spin(function (OroMainContext $context) use ($element) {
            return $context->createElement($element)->isVisible();
        });

        self::assertTrue(
            $isVisible,
            sprintf('Element "%s" is not visible, or not present on the page', $element)
        );
    }

    /**
     * Presses button with specified id|name|title|alt|value in some named section
     * Example: When I press "Add" in "General Information" section
     * Example: And I press "Add" in "General Information" section
     *
     * @When /^(?:|I )press "(?P<button>(?:[^"]|\\")*)" in "(?P<section>[^"]+)" section$/
     */
    public function pressButtonInSection($button, $section)
    {
        $button = $this->fixStepArgument($button);
        $section = $this->fixStepArgument($section);
        $page = $this->getSession()->getPage();

        $sectionContainer = $page->find('xpath', '//h4[text()="' . $section . '"]')->getParent();

        if ($sectionContainer->hasButton($button)) {
            $sectionContainer->pressButton($button);
        } else {
            $sectionContainer->clickLink($button);
        }
    }

    /**.
     * @return OroForm
     */
    protected function createOroForm()
    {
        return $this->createElement('OroForm');
    }

    /**
     * @Given /^I restart message consumer$/
     *
     * @todo remove step from scenario and step implementation in scope of BAP-14637
     */
    public function iRestartMessageConsumer()
    {
        $this->messageQueueIsolator->afterTest(new AfterIsolatedTestEvent());
        $this->messageQueueIsolator->beforeTest(new BeforeIsolatedTestEvent(null));
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
                return (int)$count;
        }
    }

    /**
     * Sets aliases to the correspond sessions
     * Example: Given sessions:
     * | First User  | first_session  |
     * | Second User | second_session |
     *
     * @Given sessions active:
     * @Given sessions:
     * @Given sessions has aliases:
     * @param TableNode $table
     */
    public function sessionsInit(TableNode $table)
    {
        $mink = $this->getMink();
        foreach ($table->getRows() as list($alias, $name)) {
            $this->sessionAliasProvider->setSessionAlias($mink, $name, $alias);
        }
    }

    /**
     * @Given /^(I |)operate as "(?P<actor>[^"]*)" under "(?P<session>[^"])"$/
     * @Given /^here is the "(?P<actor>[^"]*)" under "(?P<session>[^"])"$/
     *
     * @param string $actor
     * @param string $session
     */
    public function iOperateAsActorUnderSession($actor, $session)
    {
        $mink = $this->getMink();
        $this->sessionAliasProvider->setSessionAlias($mink, $session, $actor);
        $this->sessionAliasProvider->switchSessionByAlias($mink, $actor);
    }

    /**
     * Switch to named session window (aliases must be initialized earlier)
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::iOperateAsActorUnderSession
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::sessionsInit
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::loginAsUserWithPasswordInSession
     * To define session aliases
     *
     * Example1: I operate as the Manager
     * Example2: I act like a boss
     * Example3: I continue as the Accountant
     * Example4: I proceed as the User
     * Example5: I switch to the "Beginning" session
     *
     * @Then /^I operate as the (\w+)$/
     * @Then /^I act like a (\w+)$/
     * @Then /^I continue as the (\w+)$/
     * @Then /^I proceed as the ([^"]*)$/
     * @Then /^I switch to the "([^"]*)" session$/
     *
     * @param string $sessionAlias
     */
    public function switchToActorWindowSession($sessionAlias)
    {
        $this->sessionAliasProvider->switchSessionByAlias($this->getMink(), $sessionAlias);
    }

    /**
     * @param int $time
     */
    protected function waitForAjax($time = 60000)
    {
        return $this->getSession()->getDriver()->waitForAjax($time);
    }

    /**
     * Unselect a value in the multiselect box
     * Example: And unselect "Color" option from "Attributes"
     * Example: When I unselect "Color" option from "Attributes"
     *
     * @When /^(?:|I )unselect "(?P<option>(?:[^"]|\\")*)" option from "(?P<select>(?:[^"]|\\")*)"$/
     */
    public function unselectOption($select, $option)
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        $field = $this->getSession()->getPage()->findField($select);

        if (null === $field) {
            throw new ElementNotFoundException($driver, 'form field', 'id|name|label|value|placeholder', $select);
        }

        $selectOptionXpath = '//option[contains(.,"%s")]';
        $selectOption = $field->find(
            'xpath',
            sprintf($selectOptionXpath, $option)
        );

        if (null === $field) {
            throw new ElementNotFoundException(
                $driver,
                'select option',
                'id|name|label|value|placeholder',
                sprintf($selectOptionXpath, $option)
            );
        }

        $optionValue = $selectOption->getValue();

        $values = $field->getValue();
        if ($values !== null && is_array($values)) {
            foreach ($values as $key => $value) {
                if ($value === $optionValue) {
                    unset($values[$key]);
                    break;
                }
            }

            $field->setValue(array_values($values));
        }
    }

    /**
     * Confirm schema update and wait for success message
     * Example: And I confirm schema update
     *
     * @Then /^(?:|I )confirm schema update$/
     */
    public function iConfirmSchemaUpdate()
    {
        $this->pressButton('Update schema');
        $this->assertPageContainsText('Schema update confirmation');
        $this->pressButton('Yes, Proceed');
        $this->iShouldSeeFlashMessage('Schema updated', 'Flash Message', 120);
    }

    /**
     * Checks that element on page exists and visible
     *
     * @param string $selector
     * @return bool
     */
    public function isNodeVisible($selector)
    {
        $element = $this->getPage()->findVisible('css', $selector);

        return !is_null($element);
    }

    /**
     * Drag and Drop one element before another
     * Example: When I drag and drop "Products" before "Clearance"
     *
     * @When /^(?:|I )drag and drop "(?P<elementName>[\w\s]+)" before "(?P<dropZoneName>[\w\s]+)"$/
     * @param string $elementName
     * @param string $dropZoneName
     */
    public function iDragAndDropElementBeforeAnotherOne($elementName, $dropZoneName)
    {
        $element = $this->createElement($elementName);
        $dropZone = $this->createElement($dropZoneName);

        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $webDriverSession = $driver->getWebDriverSession();

        $source      = $webDriverSession->element('xpath', $element->getXpath());
        $destination = $webDriverSession->element('xpath', $dropZone->getXpath());

        $webDriverSession->moveto(array(
            'element' => $source->getID()
        ));
        $webDriverSession->buttondown();
        $webDriverSession->moveto([
            'element' => $destination->getID(),
            'xoffset' => 1,
            'yoffset' => 1,
        ]);
        $webDriverSession->buttonup();
    }

    /**
     * This step is used for system configuration field
     * Go to System/Configuration and see the fields with default checkboxes
     * Example: And check Use Default for "Position" field
     *
     * @Given check Use Default for :label field
     */
    public function checkUseDefaultForField($label)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->checkUseDefaultCheckbox($label);
    }

    /**
     * This step used for system configuration field
     * Go to System/Configuration and see the fields with default checkboxes
     * Example: And uncheck Use Default for "Position" field
     *
     * @Given uncheck Use Default for :label field
     */
    public function uncheckUseDefaultForField($label)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckUseDefaultCheckbox($label);
    }
}
