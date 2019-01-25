<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Doctrine\Common\Inflector\Inflector;

/**
 * Form element implementation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Form extends Element
{
    /**
     * @param TableNode $table
     * @throws ElementNotFoundException
     */
    public function fill(TableNode $table)
    {
        $isEmbeddedForm = isset($this->options['embedded-id']);
        if ($isEmbeddedForm) {
            $this->getDriver()->switchToIFrame($this->options['embedded-id']);
        }
        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            $locator = isset($this->options['mapping'][$label]) ? $this->options['mapping'][$label] : $label;
            $value = self::normalizeValue($value);

            $field = $this->findField($locator);
            if (null === $field) {
                throw new ElementNotFoundException(
                    $this->getDriver(),
                    'form field',
                    'id|name|label|value|placeholder',
                    $locator
                );
            }

            $field = $this->wrapField($label, $field);
            $field->setValue($value);
            $field->blur();
        }
        if ($isEmbeddedForm) {
            $this->getDriver()->switchToWindow();
        }
    }

    /**
     * @param string $label
     * @param string $value
     * @throws ElementNotFoundException
     */
    public function typeInField($label, $value)
    {
        $field = null;
        if (isset($this->options['mapping'][$label])) {
            $field = $this->findField($this->options['mapping'][$label]);
        }

        if (null === $field) {
            $field = $this->findFieldByLabel($label, false);
        }

        if (null === $field) {
            $field = $this->getPage()->find('named', ['field', $label]);
        }

        if (null === $field && $this->elementFactory->hasElement($label)) {
            // try to find field among defined elements
            $field = $this->elementFactory->createElement($label);
        }

        if (null === $field) {
            throw new ElementNotFoundException(
                $this->getDriver(),
                'form field',
                'id|name|label|value|placeholder',
                $label
            );
        }

        self::assertTrue($field->isVisible(), "Field with '$label' was found, but it not visible");

        $this->getDriver()->typeIntoInput($field->getXpath(), $value);
    }

    /**
     * @param TableNode $table
     */
    public function assertFields(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            $locator = isset($this->options['mapping'][$label]) ? $this->options['mapping'][$label] : $label;
            $field = $this->findField($locator);
            self::assertNotNull($field, sprintf('Field `%s` not found', $label));

            $field = $this->wrapField($label, $field);

            $expectedValue = self::normalizeValue($value);
            $fieldValue = self::normalizeValue($field->getValue());
            self::assertEquals($expectedValue, $fieldValue, sprintf('Field "%s" value is not as expected', $label));
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

    public function saveAndDuplicate()
    {
        $this->pressActionButton('Save And Duplicate');
    }

    public function save()
    {
        $this->pressActionButton('Save');
    }

    public function saveAndCreateNew()
    {
        $this->pressActionButton('Save and New');
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function findField($locator)
    {
        $selector = is_array($locator)
            ? $locator
            : ['type' => 'named', 'locator' => ['field', $locator]];
        $field = $this->find($selector['type'], $selector['locator']);

        if ($field) {
            $type = $field->getAttribute('type');
            $classes = preg_split('/\s+/', (string)$field->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);

            if ('file' === $type) {
                return $this->elementFactory->wrapElement('FileField', $field);
            }

            if ('datetime' === $type) {
                return $this->elementFactory->wrapElement('DateTimePicker', $field->getParent()->getParent());
            }

            if (in_array('custom-checkbox__input', $classes, true)) {
                return $this->elementFactory->wrapElement(
                    'PrettyCheckbox',
                    $field->getParent()->find('css', '.custom-checkbox__icon')
                );
            }

            if ('checkbox' === $type) {
                return $this->elementFactory->wrapElement('Checkbox', $field);
            }

            if (in_array('select2-offscreen', $classes, true)) {
                return $this->elementFactory->wrapElement('Select2Entity', $field);
            }

            if (in_array('select2-input', $classes, true)) {
                return $this->elementFactory->wrapElement('Select2Entities', $field);
            }

            if ('select' === $field->getTagName()) {
                return $this->elementFactory->wrapElement('Select', $field);
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
     * @param bool $failOnError
     * @return NodeElement|null
     * @throws ElementNotFoundException
     */
    protected function findFieldByLabel($locator, $failOnError = true)
    {
        if ($label = $this->findLabel($locator)) {
            $sndParent = $label->getParent()->getParent();
            $classes = preg_split('/\s+/', (string)$sndParent->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);

            if (in_array('control-group-collection', $classes, true)) {
                $elementName = Inflector::singularize(trim($label->getText())).'Collection';
                $elementName = $this->elementFactory->hasElement($elementName) ? $elementName : 'CollectionField';

                return $this->elementFactory->wrapElement($elementName, $sndParent);
            } elseif (in_array('control-group-oro_file', $classes, true)) {
                $input = $sndParent->find('css', 'input[type="file"]');

                return $this->elementFactory->wrapElement('FileField', $input);
            } elseif ($select = $sndParent->find('css', 'select')) {
                return $select;
            } elseif (in_array('control-group-checkbox', $classes, true)) {
                return $sndParent->find('css', 'input[type=checkbox]');
            } elseif (in_array('control-group-choice', $classes, true)) {
                return $this->elementFactory->wrapElement('GroupChoiceField', $sndParent->find('css', '.controls'));
            } elseif ($label->getAttribute('for')
                && $field = $sndParent->find('css', '#'.$label->getAttribute('for'))
            ) {
                return $field;
            } elseif ($label->getAttribute('for')
                && $field = $this->getPage()->find('css', '#'.$label->getAttribute('for'))
            ) {
                return $field;
            } elseif ($failOnError) {
                self::fail(sprintf('Find label "%s", but can\'t determine field type', $locator));
            }
        }

        return null;
    }

    /**
     * @param array|string $value
     * @return array|string
     */
    public static function normalizeValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::normalizeValue($item);
            }

            return $value;
        }

        $value = trim($value);

        if (0 === strpos($value, '[')) {
            return self::normalizeValue(
                array_map(
                    'trim',
                    explode(
                        ',',
                        trim($value, '[]')
                    )
                )
            );
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', trim($value))) {
            return new \DateTime($value);
        }

        $value = self::checkAdditionalFunctions($value);

        if (in_array($value, ['true', 'false'])) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }

    /**
     * Parse for string commands and execute they
     * Example: "<DateTime:August 24 11:00 AM>" would be parsed to DateTime object with provided data
     *          "Daily every 5 days, end by <Date:next month>" <> value will be replaced as well
     *
     * @param $value
     * @return \DateTime|mixed
     */
    protected static function checkAdditionalFunctions($value)
    {
        $matches = [];
        preg_match('/<(?P<function>[\w]+):(?P<value>.+)>/', $value, $matches);

        if (!empty($matches['function']) && !empty($matches['value'])) {
            if ('DateTime' === $matches['function']) {
                $value = new \DateTime($matches['value']);
            }
            if ('Date' === $matches['function']) {
                $parsed =  new \DateTime($matches['value']);
                $value = str_replace($matches[0], $parsed->format('M j, Y'), $value);
            }
        }

        return $value;
    }

    /**
     * @param string $locator
     * @return NodeElement|null
     */
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

    /**
     * Retrieves validation error message text for provided field name
     *
     * @param string $fieldName
     * @return string
     */
    public function getFieldValidationErrors($fieldName)
    {
        if (isset($this->options['mapping'][$fieldName])) {
            $field = $this->findField($this->options['mapping'][$fieldName]);
        } else {
            $field = $this->findFieldByLabel($fieldName);
        }
        $fieldId = $field->getAttribute('id');

        // This element doesn't count server side validation errors without "for" attribute
        $errorSpan = $this->find('css', "span.validation-failed[id='$fieldId-error']");

        if (!$errorSpan) {
            // Get next validation error span after element
            $errorSpan = $this->find(
                'xpath',
                sprintf(
                    '%s%s',
                    $field->getXpath(),
                    '/following-sibling::span[@class="validation-failed"]'
                )
            );
        }

        self::assertNotNull($errorSpan, "Field $fieldName has no validation errors");

        return $errorSpan->getText();
    }

    /**
     * Retrieves validation error message text for provided field name
     *
     * @param string $fieldName
     * @return array
     */
    public function getAllFieldValidationErrors($fieldName)
    {
        if (isset($this->options['mapping'][$fieldName])) {
            $field = $this->findField($this->options['mapping'][$fieldName]);
        } else {
            $field = $this->findFieldByLabel($fieldName);
        }
        $fieldId = $field->getAttribute('id');

        // This element doesn't count server side validation errors without "for" attribute
        $errorSpans = $this->findAll('css', "span.validation-failed[id='$fieldId-error']");
        $errorSpans = array_merge($this->findAll(
            'xpath',
            sprintf(
                '%s%s',
                $field->getXpath(),
                '/following-sibling::span[@class="validation-failed"]'
            )
        ), $errorSpans);

        return array_map(function (NodeElement $error) {
            return $error->getText();
        }, $errorSpans);
    }

    /**
     * @param $label
     * @param NodeElement $field
     * @return NodeElement
     */
    private function wrapField($label, NodeElement $field): NodeElement
    {
        if (isset($this->options['mapping'][$label]['element'])) {
            $field = $this->elementFactory->wrapElement(
                $this->options['mapping'][$label]['element'],
                $field
            );
        }

        return $field;
    }
}
