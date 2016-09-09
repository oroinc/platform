<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Doctrine\Common\Inflector\Inflector;

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
        $sets = $this->findAll('css', '.oro-multiselect-holder');
        self::assertNotCount(0, $sets, 'Can\'t find any set in form');

        return $this->elementFactory->wrapElement('OroForm', array_pop($sets));
    }

    public function saveAndClose()
    {
        $this->pressActionButton('Save and Close');
    }

    public function save()
    {
        $this->pressActionButton('Save');
    }

    /**
     * Choose from list Save, Save and Close, Save and New etc. on from element
     * If button is visible it'll pressed
     * If not, select from list and pressed
     *
     * @param string $actionLocator
     */
    protected function pressActionButton($actionLocator)
    {
        $button = $this->findButton($actionLocator);

        self::assertNotNull($button, sprintf('Can\'t find "%s" form action button', $actionLocator));

        if ($button->isVisible()) {
            $button->press();

            return;
        }

        $this->elementFactory->createElement('Action Button Chooser')->click();
        $button->press();
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

            if ($field->hasAttribute('type') && 'datetime' === $field->getAttribute('type')) {
                return $this->elementFactory->wrapElement('DateTimePicker', $field->getParent()->getParent());
            }

            return $field;
        }

        if ($field = $this->findFieldByLabel($locator)) {
            return $field;
        }

        if ($fieldSetLabel = $this->findFieldSetLabel($locator)) {
            return $this->elementFactory->wrapElement('FieldSet', $fieldSetLabel->getParent());
        }

        return null;
    }

    /**
     * @param string $locator Label text
     * @return NodeElement|null
     */
    protected function findFieldByLabel($locator)
    {
        if ($label = $this->findLabel($locator)) {
            $sndParent = $label->getParent()->getParent();

            if ($sndParent->hasClass('control-group-collection')) {
                $elementName = Inflector::singularize(trim($label->getText())).'Collection';
                $elementName = $this->elementFactory->hasElement($elementName) ? $elementName : 'CollectionField';

                return $this->elementFactory->wrapElement($elementName, $sndParent);
            } elseif ($sndParent->hasClass('control-group-oro_file')) {
                $input = $sndParent->find('css', 'input[type="file"]');

                return $this->elementFactory->wrapElement('FileField', $input);
            } elseif ($select = $sndParent->find('css', 'select')) {
                return $select;
            } elseif ($sndParent->hasClass('control-group-checkbox')) {
                return $sndParent->find('css', 'input[type=checkbox]');
            } elseif ($sndParent->hasClass('control-group-choice')) {
                return $this->elementFactory->wrapElement('GroupChoiceField', $sndParent->find('css', '.controls'));
            } else {
                self::fail(sprintf('Find label "%s", but can\'t determine field type', $locator));
            }
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

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', trim($value))) {
            return new \DateTime($value);
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
