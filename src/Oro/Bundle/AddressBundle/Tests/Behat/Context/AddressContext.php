<?php

namespace Oro\Bundle\AddressBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class AddressContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Assert count of addresses in entity view page
     * Example: And contact has 2 addresses
     * Example: Then contact has one address
     * Example: And two addresses should be in page
     *
     * @Then :count addresses should be in page
     * @Then /^(.*) has (?P<count>(one|two|[\d]+)) address(?:|es)$/
     */
    public function assertAddressCount($count)
    {
        $this->waitForAjax();
        $addresses = $this->getSession()->getPage()->findAll('css', 'div.map-address-list .map-item');

        self::assertCount(
            $this->getCount($count),
            $addresses,
            sprintf('Expect %s addresses but found %s', $count, count($addresses))
        );
    }

    /**
     * Assert that given address is a primary address.
     * Be aware that you can't delete primary address.
     * Example: Then LOS ANGELES address must be primary
     *
     * @Then /^(?P<address>[^"]+) address must be primary$/
     */
    public function assertPrimaryAddress($address)
    {
        $this->waitForAjax();
        $addresses = $this->getSession()->getPage()->findAll('css', 'div.map-address-list .map-item');

        /** @var NodeElement $actualAddress */
        foreach ($addresses as $actualAddress) {
            if (false !== stripos($actualAddress->getText(), $address)) {
                self::assertEquals(
                    'Primary',
                    $actualAddress->find('css', 'ul.address-book-item__labels')->getText(),
                    sprintf('Address "%s" was found but it is not primary', $address)
                );

                return;
            }
        }

        self::fail(sprintf('Address "%s" not found', $address));
    }

    /**
     * Delete all elements in collection field
     * Example: And I delete all "addresses"
     *
     * @Given /^(?:|I )delete all "(?P<field>[^"]+)"$/
     */
    public function iDeleteAllAddresses($field)
    {
        $collection = $this->elementFactory->createElement('OroForm')->findField(ucfirst(Inflector::pluralize($field)));
        self::assertNotNull($collection, sprintf('Can\'t find collection field with "%s" locator', $field));

        /** @var NodeElement $removeButton */
        while ($removeButton = $collection->find('css', '.removeRow')) {
            $removeButton->click();
        }
    }

    /**
     * Click edit icon (pencil) into address at entity view page
     * Example: And click edit LOS ANGELES address
     *
     * @Given /^click edit (?P<address>[^"]+) address$/
     */
    public function clickEditAddress($address)
    {
        $addresses = $this->getSession()->getPage()->findAll('css', 'div.map-address-list .map-item');

        /** @var NodeElement $actualAddress */
        foreach ($addresses as $actualAddress) {
            if (false !== strpos($actualAddress->getText(), $address)) {
                $actualAddress->find('css', '.item-edit-button')->click();

                return;
            }
        }

        self::fail(sprintf('Address "%s" not found', $address));
    }

    /**
     * Delete address form entity view page by clicking on trash icon given address
     * Example: When I delete Ukraine address
     *
     * @When /^(?:|I )delete (?P<address>[^"]+) address$/
     */
    public function iDeleteAddress($address)
    {
        $addresses = $this->getSession()->getPage()->findAll('css', 'div.map-address-list .map-item');

        /** @var NodeElement $actualAddress */
        foreach ($addresses as $actualAddress) {
            if (false !== strpos($actualAddress->getText(), $address)) {
                $removeButton = $actualAddress->find('css', '.item-remove-button');

                self::assertNotNull(
                    $removeButton,
                    sprintf('Found address "%s" but it has\'t delete button. Maybe it\'s primary address?', $address)
                );

                $removeButton->click();

                return;
            }
        }

        self::fail(sprintf('Address "%s" not found', $address));
    }
}
