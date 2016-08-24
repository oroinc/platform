<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\ActivityListBundle\Tests\Behat\Element\ActivityList;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class ActivityContext extends OroFeatureContext implements OroElementFactoryAware, SnippetAcceptingContext
{
    use ElementFactoryDictionary;

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
        $activityList = $this->createElement('ActivityList');
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
            $activityList = $this->createElement('ActivityList');
            $activityList->getActivityListItem($content);
        } catch (\Exception $e) {
            return;
        }

        self::fail(sprintf('Not expect to find "%s" activity item, but was found', $content));
    }

    /**
     * @When /^(?:|I )add activity comment with:$/
     */
    public function iAddActivityCommentWith(TableNode $table)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $activityList->getCollapsedItem()->addComment($table);
    }

    /**
     * @When /^(?:|I )edit "(?P<comment>[^"]+)" activity comment with:$/
     */
    public function iEditActivityCommentWith($comment, TableNode $table)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $activityList->getCollapsedItem()->editComment($comment, $table);
    }

    /**
     * @When /^(?:|I )delete "(?P<comment>[^"]+)" activity comment$/
     */
    public function iDeleteActivityCommentWith($comment)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $activityList->getCollapsedItem()->deleteComment($comment);
    }

    /**
     * @Then there is no records in activity list
     */
    public function thereIsNoRecordsInActivityList()
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
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
        $activityList = $this->createElement('ActivityList');

        self::assertCount(
            $this->getCount($number),
            $activityList->getItems(),
            sprintf('Expect that Activity list has %s items', $number)
        );
    }

    /**
     * @When /^(?:|I )go to (?P<linkLocator>(?:[nN]ewer|[oO]lder)) activities$/
     */
    public function goToNewerOrOlderActivities($linkLocator)
    {
        $link = $this->createElement('ActivityList')->findLink(ucfirst($linkLocator));

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
        $activityList = $this->createElement('ActivityList');
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
        $activityList = $this->createElement('ActivityList');
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
        $activityList = $this->createElement('ActivityList');
        $collapsedItem = $activityList->getCollapsedItem();
        $emailBody = $collapsedItem->find('css', 'div.email-body')->getHtml();

        self::assertNotFalse(
            stripos($emailBody, $content),
            sprintf('"%s" not found in "%s"', $content, $emailBody)
        );
    }

    /**
     * @Then email :arg1 should have thread icon
     */
    public function emailShouldHaveThreadIcon($content)
    {
        $this->getSession()->getDriver()->waitForAjax();
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $item = $activityList->getActivityListItem($content);
        $icon = $item->find('css', 'div.icon i');

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
        $activityList = $this->createElement('ActivityList');
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
     *
     * @Then /^(?:|I )should see (?P<text>\w+) in Contexts$/
     */
    public function iShouldSeeNameInContexts($text)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $collapsedItem = $activityList->getCollapsedItem();
        $collapsedItem->hasContext($text);
    }

    /**
     * Search text in current collapsed activity
     *
     * @Then /^(?:|I )should see (?P<text>.+) text in activity/
     */
    public function iShouldSeeTextInCollapsedActivityItem($text)
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $collapsedItem = $activityList->getCollapsedItem();

        self::assertNotFalse(
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
        $contextSelector = $this->createElement('ContextSelector');
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

        self::fail(sprintf('Can\'t find "%s" context in context selector', $needle));
    }

    /**
     * Delete all context from active (collapsed) item in activity list
     * Example: And delete all contexts from collapsed email
     *
     * @When /^(?:|I )delete all contexts from collapsed ([\w\s]*)$/
     */
    public function deleteAllContextsFromActionItem()
    {
        /** @var ActivityList $activityList */
        $activityList = $this->createElement('ActivityList');
        $collapsedItem = $activityList->getCollapsedItem();
        $collapsedItem->deleteAllContexts();
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
