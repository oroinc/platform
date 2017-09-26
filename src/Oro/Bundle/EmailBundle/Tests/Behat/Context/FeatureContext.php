<?php
namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @When /^(?:|I )click on email notification icon$/
     */
    public function iClickOnEmailNotificationIcon()
    {
        $this->elementFactory->createElement('EmailNotificationLink')->click();
    }
}
