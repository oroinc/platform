<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridPaginator;
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

        self::assertCount(
            0,
            $itemsCount,
            sprintf('Expect that Activity list not found items, but found %s', $itemsCount)
        );
    }

    /**
     * Assert number of records in activity list
     *
     * @Then /^there (?:is|are) (?P<number>(?:|one|two|\d+)) records in activity list$/
     */
    public function thereIsNumberRecordsInActivityList($number)
    {
        /** @var GridPaginator $activityListPaginator */
        $activityListPaginator = $this->createElement('ActivityListPaginator');
        $itemsCount = $activityListPaginator->getTotalRecordsCount();

        self::assertCount(
            $this->getCount($number),
            $itemsCount,
            sprintf('Expect that Activity list has %s items, but found %s', $number, $itemsCount)
        );
    }

    /**
     * Example: When go to 5 page of activity list
     *
     * @When /^(?:|I )go to (?P<pageNumber>\d+) page of activity list$/
     */
    public function goToPageOfActivityList($pageNumber)
    {
        /** @var GridPaginator $activityListPaginator */
        $activityListPaginator = $this->createElement('ActivityListPaginator');
        $activityListPaginator->find('css', 'input[type="number"]')->setValue($pageNumber);
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

        self::assertStringMatchesFormat(
            '%d',
            strpos($emailBody, $content),
            sprintf('"%s" not found in "%s"', $content, $emailBody)
        );
    }

    /**
     * @Then email :arg1 should have thread icon
     */
    public function emailShouldHaveThreadIcon($content)
    {
        $item = $this->getActivityListItem($content);
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
        $item = $this->getActivityListItem($content);
        $threadEmails = $item->findAll('css', 'div.thread-view div.email-info');

        self::assertCount(
            $this->getCount($emailsCount),
            count($threadEmails),
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
        $collapsedItem = $this->getCollapsedItem();
        $contexts = $collapsedItem->findAll('css', 'div.activity-context-activity-list div.context-item a');

        /** @var NodeElement $context */
        foreach ($contexts as $context) {
            if (false !== stripos($context->getText(), $text)) {
                return;
            }
        }

        throw new ExpectationException(
            sprintf('Context with "%s" name not found', $text),
            $this->getSession()->getDriver()
        );
    }

    /**
     * Search text in current collapsed activity
     *
     * @Then /^(?:|I )should see (?P<text>.+) text in activity/
     */
    public function iShouldSeeTextInCollapsedActivityItem($text)
    {
        self::assertStringMatchesFormat(
            '%d',
            stripos($this->getCollapsedItem()->getText(), $text),
            sprintf('Can\'t find "%s" image name in collapsed activity item', $text)
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

        throw new ExpectationException(
            sprintf('Can\'t find "%s" context in context selector', $needle),
            $this->getSession()->getDriver()
        );
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
     * @return NodeElement Collapsed activity element
     */
    protected function getCollapsedItem()
    {
        $items = $this->getSession()->getPage()->findAll('css', 'div.accordion-body');
        $collapsedItem = array_filter($items, function (NodeElement $element) {
            return $element->hasClass('in');
        });

        self::assertTrue(
            0 < count($collapsedItem),
            'Not found collapsed items in activity list'
        );

        return array_shift($collapsedItem);
    }

    /**
     * @param string $content
     * @return NodeElement Activity element
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
