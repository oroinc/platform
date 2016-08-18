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
     * @Then /^(?:|I )click on "(?P<comment>[^"]+)" attachment thumbnail$/
     */
    public function commentAttachmentShouldProperlyWork($comment)
    {
        /** @var CommentItem $commentItem */
        $commentItem = $this->elementFactory->findElementContains('CommentItem', $comment);
        self::assertTrue($commentItem->isValid(), sprintf('Comment with "%s" text not found', $comment));

        $commentItem->clickOnAttachmentThumbnail();
    }
}
