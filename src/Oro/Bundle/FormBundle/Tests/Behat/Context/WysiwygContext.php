<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class WysiwygContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Example: When I fill in WYSIWYG "CMS Page Content" with "Content"
     *
     * @When /^(?:|I )fill in WYSIWYG "(?P<wysiwygElementName>[^"]+)" with "(?P<text>(?:[^"]|\\")*)"$/
     * @param string $wysiwygElementName
     * @param string $text
     */
    public function fillWysiwygContentField($wysiwygElementName, $text)
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $this->getSession()->wait(300);
        $function = sprintf(
            '(function(){
                $("#%s")
                    .trigger("wysiwyg:disable")
                    .val("%s")
                    .trigger("change")
                    .trigger("wysiwyg:enable");
            })()',
            $wysiwygContentElement->getAttribute('id'),
            $text
        );

        $this->getSession()->executeScript($function);
        $this->getSession()->wait(300);
    }

    /**
     * @Then /^(?:|I )should see text matching (?P<pattern>"(?:[^"]|\\")*") in WYSIWYG editor$/
     */
    public function assertWysiwygEditorMatchesText(string $pattern): void
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);
        $this->assertSession()->pageTextMatches($this->fixStepArgument($pattern));
        $this->getDriver()->switchToIFrame(null);
    }

    /**
     * @Then /^(?:|I )should not see text matching (?P<pattern>"(?:[^"]|\\")*") in WYSIWYG editor$/
     */
    public function assertWysiwygEditorNotMatchesText(string $pattern): void
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);
        $this->assertSession()->pageTextNotMatches($this->fixStepArgument($pattern));
        $this->getDriver()->switchToIFrame(null);
    }

    /**
     * Example: When I click on "WysiwygFileTypeBlock" with title "File name" in WYSIWYG editor
     *
     * @When /^(?:|I )click on "(?P<selector>[^"]+)" with title "(?P<title>[^"]+)" in WYSIWYG editor$/
     */
    public function iClickOnElementWithTitleInWysiwygEditor(string $selector, string $title)
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        $element = $this->findElementContains($selector, $title);
        self::assertTrue(
            $element->isValid(),
            sprintf('Element "%s" with title "%s" not found in WYSIWYG editor', $selector, $title)
        );
        $element->click();

        $this->getDriver()->switchToWindow();
    }

    /**
     * Example: When I click on "WysiwygTextTypeBlock" in WYSIWYG editor
     *
     * @When /^(?:|I )click on "(?P<selector>[^"]+)" in WYSIWYG editor$/
     */
    public function iClickOnElementInWysiwygEditor(string $selector)
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        $element = $this->createElement($selector);
        self::assertTrue(
            $element->isValid(),
            sprintf('Element "%s" not found in WYSIWYG editor', $selector)
        );
        $element->click();

        $this->getDriver()->switchToWindow();
    }
}
