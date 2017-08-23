<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    OroPageObjectAware,
    FixtureLoaderAwareInterface
{
    use PageObjectDictionary;

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
     * Load "emails.yml" alice fixture from EmailBundle suite
     *
     * @Given /^(?:|I )receive new emails$/
     */
    public function iHaveNewEmails()
    {
        $this->fixtureLoader->loadFixtureFile('OroEmailBundle:emails.yml');
    }

    /**
     * Assert number of emails in email notification icon
     * Example: Then email notification icon show 5 emails
     *
     * @Then email notification icon show :number emails
     */
    public function emailNotificationIconShowEmails($number)
    {
        self::assertEquals($number, $this->createElement('EmailNotificationLink')->getText());
    }

    /**
     * Example: Then I click on email notification icon
     *          And I should see 4 emails in email list
     *
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
     * Example: Then I click on email notification icon
     *          And I should see 4 emails in email list
     *          And all emails in email list must be new
     *
     * @Then /^(?P<emailCount>(?:|one|two|\d+)) emails in email list must be new$/
     */
    public function emailsInEmailListMustBeNew($emailCount)
    {
        $emails = $this->getPage()->findAll('css', '.short-emails-list ul.items li.highlight');
        self::assertCount($this->getCount($emailCount), $emails);
    }

    /**
     * Example: Then I click on email notification icon
     *          And I click on "Merry Christmas" email title
     *
     * @Given I click on :emailTitle email title
     */
    public function iClickOnEmailTitle($emailTitle)
    {
        $title = $this->createElement('ShortEmailList')->findElementContains('EmailTitle', $emailTitle);
        self::assertNotNull($title, "Email title '$emailTitle' not found");

        $title->click();
    }

    /**
     * Example: Given 3 emails in email list must be new
     *          When I click on email notification icon
     *          And I mark "Merry Christmas" email as unread
     *          Then 4 emails in email list must be new
     *
     * @Given I mark :emailTitle email as unread
     */
    public function iMarkEmailAsUnread($emailTitle)
    {
        $email = $this->createElement('ShortEmailList')->findElementContains('EmailListItem', $emailTitle);
        self::assertNotNull($email, "Email with '$emailTitle' title not found");

        $email->getElement('ReadUnreadIcon')->click();
    }
}
