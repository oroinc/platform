<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @Given uncheck Use Default for :label field
     */
    public function uncheckUseDefaultForField($label)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckUseDefaultCheckbox($label);
    }

    /**
     * @When I save setting
     */
    public function iSaveSetting()
    {
        $this->getPage()->pressButton('Save settings');
    }

    /**
     * @Then menu must be on left side
     * @Then menu is on the left side
     */
    public function menuMustBeOnLeftSide()
    {
        self::assertFalse($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }

    /**
     * @Then menu must be at top
     * @Then menu is at the top
     */
    public function menuMustBeOnRightSide()
    {
        self::assertTrue($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }

    /**
     * @When /^(?:|I )click "(?P<link>[^"]+)" in shortcuts search results$/
     */
    public function clickInShortcutsSearchResults($link)
    {
        $result = $this->spin(function (FeatureContext $context) use ($link) {
            $result = $context->getPage()->find('css', sprintf('li[data-value="%s"] a', $link));

            if ($result && $result->isVisible()) {
                return $result;
            }

            return false;
        });

        self::assertNotFalse($result, sprintf('Link "%s" not found', $link));

        $result->click();
    }

    /**
     * @When /^(?:|I )(?P<action>(pin|unpin)) page$/
     */
    public function iPinPage($action)
    {
        $button = $this->getPage()->findButton('Pin/unpin the page');
        self::assertNotNull($button, 'Pin/Unpin button not found on page');

        $activeClass = 'gold-icon';

        if ('pin' === $action) {
            if ($button->hasClass($activeClass)) {
                self::fail('Can\'t pin tab that already pinned');
            }

            $button->press();
        } elseif ('unpin' === $action) {
            if (!$button->hasClass($activeClass)) {
                self::fail('Can\'t unpin tab that not pinned before');
            }

            $button->press();
        }
    }

    /**
     * @Given /^(?P<link>[\w\s]+) link must not be in pin holder$/
     */
    public function usersLinkMustNotBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertFalse($linkElement->isValid(), "Link with '$link' anchor found, but it's not expected");
    }

    /**
     * @Then /^(?P<link>[\w\s]+) link must be in pin holder$/
     */
    public function linkMustBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");
    }

    /**
     * @When /^(?:|I )follow (?P<link>[\w\s]+) link in pin holder$/
     */
    public function followUsersLinkInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");

        $linkElement->click();
    }

    /**
     * @When press Create User button
     */
    public function pressCreateUserButton()
    {
        $this->getPage()->find('css', 'div.title-buttons-container a.btn-primary')->click();
    }

    /**
     * @When /^(?:|I )click icon bars$/
     */
    public function clickBarsIcon()
    {
        $this->getPage()->find('css', 'i.icon-bars')->click();
    }

    /**
     * @When /^(?:|I )go to next pages:$/
     */
    public function goToPages(TableNode $table)
    {
        /** @var MainMenu $menu */
        $menu = $this->createElement('MainMenu');

        foreach ($table->getRows() as $row) {
            $menu->openAndClick($row[0]);
            $this->waitForAjax();
        }
    }

    /**
     * @Then /^(?P<tab>(History|Most Viewed|Favorites)) must looks like:$/
     */
    public function historyMustLooksLike($tab, TableNode $table)
    {
        $content = $this->createElement($tab.' Content');

        if (!$content->isVisible()) {
            $this->chooseQuickMenuTab($tab);
        }

        self::assertTrue($content->isVisible());

        /** @var NodeElement $item */
        foreach ($content->findAll('css', 'ul li a') as $key => $item) {
            self::assertEquals(
                $table->getRow($key)[0],
                trim($item->getText())
            );
        }
    }

    /**
     * @Then /^(?P<tab>(History|Most Viewed|Favorites)) is empty$/
     */
    public function tabContentIsEmpty($tab)
    {
        $content = $this->createElement($tab.' Content');

        if (!$content->isVisible()) {
            $this->chooseQuickMenuTab($tab);
        }

        self::assertTrue($content->isVisible());
        self::assertCount(0, $content->findAll('css', 'ul li a'));
    }

    /**
     * @When /^(?:|I )choose (?P<link>(History|Most Viewed|Favorites)) tab$/
     */
    public function chooseQuickMenuTab($link)
    {
        $linkElement = $this->getPage()->findLink($link);
        self::assertNotNull($linkElement);

        if (!$linkElement->isVisible()) {
            $this->clickBarsIcon();
        }

        $linkElement->click();
    }

    /**
     * @When /^(?:|I )(?P<action>(add|remove)) page (to|from) favorites$/
     */
    public function iAddOrRemovePageToFavorites($action)
    {
        $button = $this->createElement('AddToFavoritesButton');
        self::assertTrue($button->isVisible());

        $activeClass = 'gold-icon';

        if ('add' === $action) {
            if ($button->hasClass($activeClass)) {
                self::fail('Can\'t add page to favorites, it is already in favorites');
            }

            $button->press();
        } elseif ('remove' === $action) {
            if (!$button->hasClass($activeClass)) {
                self::fail('Can\'t remove page from favorites, it is not in favorites currently');
            }

            $button->press();
        }
    }

    /**
     * @When /^(?:|I )remove "(?P<record>[^"]+)" from favorites$/
     */
    public function removeFromFavorites($record)
    {
        $content = $this->createElement('Favorites Content');
        self::assertTrue($content->isVisible());

        $item = $content->findElementContains('QuickMenuContentItem', $record);
        self::assertTrue($item->isVisible());

        $item->find('css', 'button.close')->press();
    }

    /**
     * @Then there are no pages in favorites
     */
    public function thereAreNoPagesInFavorites()
    {
        $content = $this->createElement('Favorites Content');
        self::assertTrue($content->isVisible());

        self::assertCount(0, $content->findAll('css', 'ul li'));
    }

    /**
     * @Then /^(?:|I )click on "(?P<record>[^"]+)" in Favorites$/
     */
    public function iClickOnInFavorites($record)
    {
        $content = $this->createElement('Favorites Content');
        self::assertTrue($content->isVisible());

        $item = $content->findElementContains('QuickMenuContentItem', $record);
        self::assertTrue($item->isVisible());

        $item->find('css', 'a')->click();
    }
}
