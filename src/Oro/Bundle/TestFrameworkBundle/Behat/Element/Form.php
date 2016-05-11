<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;

class Form extends Element
{
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->fillField($row[0], $row[1]);
        }
    }
}
