<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;

class Form extends Element
{
    /**
     * @param TableNode $table
     * @throws ElementNotFoundException
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $locator = isset($this->options['mapping'][$row[0]]) ? $this->options['mapping'][$row[0]] : $row[0];
            $this->fillField($locator, $row[1]);
        }
    }
}
