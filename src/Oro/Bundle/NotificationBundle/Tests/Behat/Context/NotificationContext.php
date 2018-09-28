<?php

namespace Oro\Bundle\NotificationBundle\Tests\Behat\Context;

use Oro\Bundle\NotificationBundle\Tests\Behat\Element\AdditionalAssociationsSection;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class NotificationContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Sets additional association's checkbox by association's name
     *
     * Example: And I check "Customer User" additional association
     *
     * @Then /^(?:|I )check "(?P<name>([\w\s]+))" additional association$/
     *
     * @param string $name
     */
    public function checkAdditionalAssociation(string $name)
    {
        $additionalAssociations = $this->getAdditionalAssociationsSectionElement();
        $additionalAssociations->setCheckBoxByName($name);
    }

    /**
     * @return AdditionalAssociationsSection
     */
    protected function getAdditionalAssociationsSectionElement()
    {
        return $this->elementFactory->createElement('Additional Associations Section');
    }
}
