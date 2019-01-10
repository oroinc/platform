<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ActivityListBundle\Tests\Behat\Element\ActivityList;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UIBundle\Tests\Behat\Element\ContextSelector;

class ActivityContext extends OroFeatureContext implements OroPageObjectAware, SnippetAcceptingContext
{
    use PageObjectDictionary;

    /**
     * Assert that activity item with given text is present in activity list
     * Example: And should see "Fwd: Re: Work for you" email in activity list
     *
     * @Then /^(?:|I )should see "(?P<content>[^"]*)" ([\w\s]*) in activity list$/
     */
    public function shouldSeeRecordInActivityList($content)
    {
        $this->getSession()->getDriver()->waitForAjax();
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $activityList->getActivityListItem($content);
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
            /** @var ActivityList $activityList */
            $activityList = $this->createElement('Activity List');
            $activityList->getActivityListItem($content);
        } catch (\Exception $e) {
            return;
        }

        self::fail(sprintf('Not expect to find "%s" activity item, but was found', $content));
    }

    /**
     * Get collapsed activity item and comment it
     * Example: Given collapse "Contact with Charlie" in activity list
     *          When I add activity comment with:
     *            | Message    | Ask how his mood |
     *            | Attachment | cat.jpg          |
     *
     * @When /^(?:|I )add activity comment with:$/
     */
    public function iAddActivityCommentWith(TableNode $table)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $activityList->getCollapsedItem()->addComment($table);
    }

    /**
     * Edit comment in collapsed activity item
     * Example: Given collapse "Contact with Charlie" in activity list
     *          When I edit "Ask how his mood" activity comment with:
     *            | Message    | Just wish a nice day |
     *
     * @When /^(?:|I )edit "(?P<comment>[^"]+)" activity comment with:$/
     */
    public function iEditActivityCommentWith($comment, TableNode $table)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $activityList->getCollapsedItem()->editComment($comment, $table);
    }

    /**
     * Delete comment from collapsed activity item
     * Example: Given I collapse "Contact with Charlie" in activity list
     *          And delete "Just wish a nice day" activity comment
     *
     * @When /^(?:|I )delete "(?P<comment>[^"]+)" activity comment$/
     */
    public function iDeleteActivityCommentWith($comment)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $activityList->getCollapsedItem()->deleteComment($comment);
    }

    /**
     * Assert that activity list is empty
     *
     * @Then /^(?:|I )see no records in activity list$/
     */
    public function thereIsNoRecordsInActivityList()
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $items = $activityList->getItems();

        self::assertCount(
            0,
            $items,
            sprintf('Expect that Activity list not found items, but found %s', count($items))
        );
    }

    /**
     * Assert number of records in activity list
     *
     * @Then /^there (?:is|are) (?P<number>(?:|one|two|\d+)) record(?:|s) in activity list$/
     */
    public function thereIsNumberRecordsInActivityList($number)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');

        self::assertCount(
            $this->getCount($number),
            $activityList->getItems(),
            sprintf('Expect that Activity list has %s items', $number)
        );
    }

    /**
     * Click on paginations buttons in activity list
     * Example: When go to older activities
     * Example: When go to newer activities
     *
     * @When /^(?:|I )go to (?P<linkLocator>(?:[nN]ewer|[oO]lder)) activities$/
     */
    public function goToNewerOrOlderActivities($linkLocator)
    {
        $link = $this->createElement('Activity List')->findButton(ucfirst($linkLocator));

        if (!$link) {
            self::fail(sprintf('Can\'t find "%s" button', $linkLocator));
        } elseif ($link->getParent()->hasClass('disabled')) {
            self::fail(sprintf('Button "%s" is disabled', $linkLocator));
        }

        $link->click();
    }

    /**
     * Find activity item in activity list and collapse it
     * Example: When I collapse "Fwd: Re: Work for you" in activity list
     *
     * @When /^(?:|I )collapse "(?P<content>[^"]*)" in activity list$/
     */
    public function iCollapseActivityListItem($content)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $item = $activityList->getActivityListItem($content);
        $item->collapse();
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
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $item = $activityList->getActivityListItem($content);
        $link = $item->getActionLink($action);

        self::assertNotNull($link, sprintf('"%s" activity item was found, but "%s" action not', $content, $action));
        $link->click();
    }

    /**
     * Assert that email body in activity list has substring
     * Example: Then I should see "We have new role for you" in email body
     *
     * @Then /^(?:|I )should see "(?P<content>(?:[^"]|\\")*)" in email body$/
     */
    public function iShouldSeeInEmailBody($content)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $collapsedItem = $activityList->getCollapsedItem();
        $emailBody = $collapsedItem->find('css', 'div.email-body')->getHtml();

        self::assertNotFalse(
            stripos($emailBody, $content),
            sprintf('"%s" not found in "%s"', $content, $emailBody)
        );
    }

    /**
     * Assert email thread icon
     * Example: Then email "Work for you" should have thread icon
     *
     * @Then email :arg1 should have thread icon
     */
    public function emailShouldHaveThreadIcon($content)
    {
        $this->getSession()->getDriver()->waitForAjax();
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $item = $activityList->getActivityListItem($content);
        $icon = $item->find('css', 'div.icon span');

        self::assertTrue(
            $icon->hasClass('icon-email-thread'),
            sprintf('Expect that "%s" has thread email icon, but it hasn\'t', $content)
        );
    }

    /**
     * Assert count of emails in thread
     * Example: And email thread "Work for you" should have two emails
     *
     * @Then /^email thread "(?P<content>(?:[^"]|\\")*)" should have (?P<emailsCount>(?:|one|two|\d+)) emails$/
     */
    public function emailShouldHaveTwoEmails($content, $emailsCount)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $item = $activityList->getActivityListItem($content);
        $threadEmails = $item->findAll('css', 'div.thread-view div.email-info');

        self::assertCount(
            $this->getCount($emailsCount),
            $threadEmails,
            sprintf('Expect %s number of emails in thread, but get %s', $emailsCount, count($threadEmails))
        );
    }

    /**
     * Assert that one of contexts contains text
     * Example: And I should see Charlie in Contexts
     *
     * @Then /^(?:|I )should see (?P<text>\w+) in Contexts$/
     */
    public function iShouldSeeNameInContexts($text)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $collapsedItem = $activityList->getCollapsedItem();
        $collapsedItem->hasContext($text);
    }

    /**
     * Search text in current collapsed activity
     * Example: Then I should see Ask how his mood text in activity
     *
     * @Then /^(?:|I )should see (?P<text>.+) text in activity$/
     *
     * @param string $text
     */
    public function iShouldSeeTextInCollapsedActivityItem(string $text)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $collapsedItem = $activityList->getCollapsedItem();

        self::assertNotFalse(
            stripos($collapsedItem->getText(), $text),
            sprintf('Can\'t find "%s" in collapsed activity item', $text)
        );
    }

    /**
     * Search text in current collapsed activity
     * Example: Then I should not see Ask how his mood text in activity
     *
     * @Then /^(?:|I )should not see (?P<text>.+) text in activity$/
     *
     * @param string $text
     */
    public function iShouldNotSeeTextInCollapsedActivityItem(string $text)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $collapsedItem = $activityList->getCollapsedItem();

        self::assertFalse(
            stripos($collapsedItem->getText(), $text),
            sprintf('Can\'t find "%s" in collapsed activity item', $text)
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
        /** @var ContextSelector $contextSelector */
        $contextSelector = $this->createElement('ContextSelector');
        $contextSelector->select($needle);
    }

    /**
     * Example: And delete "John Doe" context from collapsed email
     *
     * @When /^(?:|I )delete "(?P<content>[\w\s]+)" context from collapsed ([\w\s]*)$/
     * @param string $content
     */
    public function deleteContextFromActionItem($content)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('Activity List');
        $collapsedItem = $activityList->getCollapsedItem();
        $collapsedItem->deleteContext($content);
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
