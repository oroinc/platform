<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

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
            $value = $this->normalizeValue($row[1]);
            $this->fillField($locator, $value);
        }
    }

    /**
     * Find last embed form in collection of fieldset
     * See collection address in Contact (CRM) form for example
     *
     * @return Form|null
     */
    public function getLastSet()
    {
        $maxSet = 10;
        $prevSet = $this->find('css', 'div[data-content="0"]');

        for ($i = 2; $i <= $maxSet; $i++) {
            $set = $this->find('css', sprintf('div[data-content="%s"]', $i));

            if (!$set) {
                return $this->elementFactory->wrapElement('OroForm', $prevSet);
            }

            $prevSet = $set;
        }

        return null;
    }

    public function saveAndClose()
    {
        $this->pressButton('Save and Close');
    }

    /**
     * @param string $locator
     */
    public function pressEntitySelectEntityButton($locator)
    {
        $field = $this->findField($locator);

        if (null !== $field) {
            $field = $this->findLabel($locator);
        }

        $this->findElementInParents($field, '.entity-select-btn')->click();
    }

    /**
     * {@inheritdoc}
     */
    public function findField($locator)
    {
        if ($field = parent::findField($locator)) {
            if ($field->hasAttribute('type') && 'file' === $field->getAttribute('type')) {
                return $this->elementFactory->wrapElement('FileField', $field);
            }

            return $field;
        }

        if ($label = $this->findLabel($locator)) {
            $sndParent = $label->getParent()->getParent();
            if ($sndParent->hasClass('control-group-collection')) {
                return $this->elementFactory->wrapElement('CollectionField', $sndParent);
            } elseif ($sndParent->hasClass('control-group-oro_file')) {
                $input = $sndParent->find('css', 'input[type="file"]');

                return $this->elementFactory->wrapElement('FileField', $input);
            } elseif ($select = $sndParent->find('css', 'select')) {
                return $select;
            } elseif ($sndParent->hasClass('control-group-checkbox')) {
                return $sndParent->find('css', 'input[type=checkbox]');
            } else {
                throw new ExpectationException(
                    sprintf('Find label "%s", but can\'t detemine field type', $locator),
                    $this->getDriver()
                );
            }
        }

        if ($fieldSetLabel = $this->findFieldSetLabel($locator)) {
            return $this->elementFactory->wrapElement('FieldSet', $fieldSetLabel->getParent());
        }

        return null;
    }

    /**
     * @param string $value
     * @return array|string
     */
    protected function normalizeValue($value)
    {
        $value = trim($value);

        if (0 === strpos($value, '[')) {
            return explode(',', trim($value, '[]'));
        }

        return $value;
    }

    protected function findFieldSetLabel($locator)
    {
        $labelSelector = sprintf("h5.user-fieldset:contains('%s')", $locator);

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
