<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\FormFiller;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\DocumentElement;

class FormFiller
{
    /**
     * @var array
     */
    protected $formMapping = [];

    /**
     * @param array $formMapping
     */
    public function addMapping(array $formMapping)
    {
        $this->formMapping = array_merge($this->formMapping, $formMapping);
    }

    /**
     * @param string $name
     * @param DocumentElement $element
     * @param TableNode $table
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Exception
     */
    public function fillForm($name, DocumentElement $element, TableNode $table)
    {
        if (!array_key_exists($name, $this->formMapping)) {
            throw new \Exception(sprintf('Can\'t find "%s" field', $name));
        }

        foreach ($table as $row) {
            if (!array_key_exists($row['label'], $this->formMapping[$name])) {
                throw new \Exception(sprintf('Can\'t find "%s" label for "%s" form', $row['label'], $name));
            }

            $element->fillField($this->formMapping[$name][$row['label']], $row['value']);
        }
    }
}
