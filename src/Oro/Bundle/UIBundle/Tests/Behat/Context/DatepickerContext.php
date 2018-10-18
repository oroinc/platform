<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\DateTimePicker;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class DatepickerContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Asserts header in datepicker.
     *
     * Example: Then I should see following header in "Datepicker":
     *             | S | M | T | W | T | F | S |
     *
     * @Then /^(?:|I )should see following header in "(?P<datepicker>[^"]+)":$/
     *
     * @param string $datepicker
     * @param TableNode $table
     */
    public function iShouldSeeFollowingHeaderInDatepicker(string $datepicker, TableNode $table)
    {
        $data = $table->getRows();
        self::assertNotEmpty($data);

        /** @var DateTimePicker $form */
        $form = $this->createElement($datepicker);

        self::assertTrue($form->isVisible());
        self::assertEquals(reset($data), $form->getHeader());
    }
}
