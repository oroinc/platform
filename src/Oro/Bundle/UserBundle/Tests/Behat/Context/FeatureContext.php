<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Context;

use Oro\Bundle\CommentBundle\Tests\Behat\Element\CommentItem;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @Then /^(?:|I )click on "(?P<text>[^"]+)" attachment thumbnail$/
     */
    public function commentAttachmentShouldProperlyWork($text)
    {
        /** @var CommentItem $commentItem */
        $commentItem = $this->elementFactory->findElementContains('CommentItem', $text);
        self::assertTrue($commentItem->isValid(), sprintf('Comment with "%s" text not found', $text));

        $commentItem->clickOnAttachmentThumbnail();
    }

    /**
     * @Then /^download link for "(?P<text>[^"]+)" attachment should work$/
     */
    public function downloadLinkForAttachmentShouldWork($text)
    {
        /** @var CommentItem $commentItem */
        $commentItem = $this->elementFactory->findElementContains('CommentItem', $text);
        self::assertTrue($commentItem->isValid(), sprintf('Comment with "%s" text not found', $text));

        $commentItem->checkDownloadLink();
    }
}
