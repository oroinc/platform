<?php

namespace Oro\Bundle\ReminderBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\Xpath\Manipulator;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;

class ReminderCollection extends CollectionField
{
    protected $unitChoices = [
        'minutes' => ReminderInterval::UNIT_MINUTE,
        'hours' => ReminderInterval::UNIT_HOUR,
        'days' => ReminderInterval::UNIT_DAY,
        'weeks' => ReminderInterval::UNIT_WEEK,
    ];

    /**
     * @param TableNode $table
     */
    public function setValue($table)
    {
        $this->removeAllRows();
        $this->addNewRows($table);

        $rows = $this->findAll('css', '.oro-multiselect-holder');

        foreach ($table as $values) {
            /** @var NodeElement $row */
            $row = array_shift($rows);
            $rowNumber = $row->getParent()->getAttribute('data-content');

            $method = sprintf('//select[contains(@id,"reminders_%s_method")]', $rowNumber);
            $intervalUnit = sprintf('//select[contains(@id,"reminders_%s_interval_unit")]', $rowNumber);
            $intervalNumber = sprintf('//input[contains(@id,"reminders_%s_interval_number")]', $rowNumber);

            $row->find('xpath', $method)->setValue($values['Method']);
            $row->find('xpath', $intervalNumber)->setValue($values['Interval number']);
            $row->find('xpath', $intervalUnit)->setValue(
                $this->unitChoices[Inflector::pluralize($values['Interval unit'])]
            );
        }
    }
}
