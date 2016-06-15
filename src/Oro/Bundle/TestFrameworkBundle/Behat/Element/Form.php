<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
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

    public function saveAndClose()
    {
        $this->pressButton('Save and Close');
    }

    /**
     * {@inheritdoc}
     */
    public function selectFieldOption($locator, $value, $multiple = false)
    {
        $field = $this->findField($locator);

        if (null !== $field) {
            $field->selectOption($value, $multiple);
            return;
        }

        $label = $this->findLabel($locator);

        if (null === $label) {
            throw new ElementNotFoundException($this->getDriver(), 'label', 'text', $locator);
        }

        $field = $this->findElementInParents($label, 'select');

        if (null === $field) {
            throw new ElementNotFoundException($this->getDriver(), 'select field', 'label', $locator);
        }

        $field->selectOption($value);
    }

    /**
     * Finds label with specified locator.
     *
     * @param string $locator label text
     *
     * @return NodeElement|null
     */
    public function findLabel($locator)
    {
        $labelSelector = sprintf("label:contains('%s')", $locator);

        return $this->find('css', $labelSelector);
    }

    /**
     * @param NodeElement $element
     * @param string $type etc. input|label|select
     * @param int $deep Count of parent elements that will be inspected for contains searched element type
     * @return NodeElement|null First found element with given type
     */
    protected function findElementInParents(NodeElement $element, $type, $deep = 3)
    {
        $field = null;
        $parentElement = $element->getParent();
        $i = 0;

        do {
            $parentElement = $parentElement->getParent();
            $field = $this->find('css', $type);
            $i++;
        } while ($field === null && $i < $deep);

        return $field;
    }
}
