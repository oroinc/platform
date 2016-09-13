<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends OroFeatureContext implements
    OroElementFactoryAware,
    FixtureLoaderAwareInterface
{
    use ElementFactoryDictionary;

    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * {@inheritdoc}
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader)
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    /**
     * @When /^(?:|I )click on email notification icon$/
     */
    public function iClickOnEmailNotificationIcon()
    {
        $this->createElement('EmailNotificationLink')->click();
    }

    /**
     * @Given /^(?:|I )receive new emails$/
     */
    public function iHaveNewEmails()
    {
        $this->fixtureLoader->loadFixtureFile('emails.yml');
    }

    /**
     * @Then email notification icon show :number emails
     */
    public function emailNotificationIconShowEmails($number)
    {
        self::assertEquals($number, $this->createElement('EmailNotificationLink')->getText());
    }

    /**
     * @Then /^(?:|I )should see (?P<emailCount>(?:|one|two|\d+)) emails in email list$/
     */
    public function iShouldSeeNewEmailsInEmailList($emailCount)
    {
        $emails = $this->getPage()->findAll('css', '.short-emails-list ul.items li');
        self::assertCount($this->getCount($emailCount), $emails);
    }

    /**
     * @Then all emails in email list must be new
     */
    public function allEmailsInEmailListMustBeNew()
    {
        $emails = $this->getPage()->findAll('css', '.short-emails-list ul.items li');

        /** @var NodeElement $email */
        foreach ($emails as $key => $email) {
            self::assertTrue($email->hasClass('highlight'), "Email $key is not new");
        }
    }

    /**
     * @Then /^(?P<emailCount>(?:|one|two|\d+)) emails in email list must be new$/
     */
    public function emailsInEmailListMustBeNew($emailCount)
    {
        $emails = $this->getPage()->findAll('css', '.short-emails-list ul.items li.highlight');
        self::assertCount($this->getCount($emailCount), $emails);
    }

    /**
     * @Given I click on :emailTitle email title
     */
    public function iClickOnEmailTitle($emailTitle)
    {
        $title = $this->createElement('ShortEmailList')->findElementContains('EmailTitle', $emailTitle);
        self::assertNotNull($title, "Email title '$emailTitle' not found");

        $title->click();
    }

    /**
     * @Given I mark :emailTitle email as unread
     */
    public function iMarkEmailAsUnread($emailTitle)
    {
        $email = $this->createElement('ShortEmailList')->findElementContains('EmailListItem', $emailTitle);
        self::assertNotNull($email, "Email with '$emailTitle' title not found");

        $email->getElement('ReadUnreadIcon')->click();
    }

    /**
     * @Then I should see an email form
     */
    public function iShouldSeeAnEmailForm()
    {
        self::assertTrue($this->createElement('EmailForm')->isValid());
    }

    /**
     * @Then it must contains next values:
     */
    public function itMustContainsNextValues(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('EmailFormView');
        $mapping = $form->getOption('mapping');
        $page = $this->getPage();

        foreach ($table->getRows() as $row) {
            $field = $page->find('css', $mapping[$row[0]]);
            self::assertNotNull($field);

            $value = $field->getText() ? $field->getText() : $field->getValue();
            self::assertEquals($row[1], $value);
        }
    }
}
