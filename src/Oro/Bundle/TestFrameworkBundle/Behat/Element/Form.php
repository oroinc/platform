<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Carbon\Carbon;
use Oro\Bundle\TestFrameworkBundle\Behat\Environment\BehatSecretsReader;
use Oro\Bundle\TestFrameworkBundle\Exception\BehatSecretsReaderException;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;

/**
 * Form element implementation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Form extends Element
{
    /**
     * @throws ElementNotFoundException
     */
    public function fill(TableNode $table)
    {
        $isEmbeddedForm = isset($this->options['embedded-id']);
        if ($isEmbeddedForm) {
            $this->getDriver()->switchToIFrame($this->options['embedded-id']);
        }
        foreach ($table->getRows() as $row) {
            [$label, $value] = $row;
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
            $this->getDriver()->waitForAjax();
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
    public function typeInField($label, $value, $clearField = true)
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

        $this->getDriver()->typeIntoInput($field->getXpath(), $value, $clearField);
    }

    public function assertFields(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$label, $value] = $row;
            $locator = isset($this->options['mapping'][$label]) ? $this->options['mapping'][$label] : $label;
            $field = $this->findField($locator);
            self::assertNotNull($field, sprintf('Field `%s` not found', $label));

            $field = $this->wrapField($label, $field);

            $expectedValue = self::normalizeValue($value);

            $result = $this->spin(function () use ($field, $expectedValue) {
                $fieldValue = self::normalizeValue($field->getValue());

                // Comparison operator is not strict intentionally.
                return $expectedValue == $fieldValue;
            }, 3);

            self::assertTrue($result, sprintf('Field "%s" value is not as expected', $label));
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

    public function saveAndReturn()
    {
        $this->pressActionButton('Save and Return');
    }

    /**
     * Choose from list Save, Save and Close, Save and New etc. on from element
     * If button is visible it'll pressed
     * If not, select from list and pressed
     *
     * @param string $actionLocator
     */
    public function pressActionButton($actionLocator)
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
            $isExternalFileField = $field->getAttribute('data-is-external-file') === '1';

            if ('file' === $type) {
                return $this->elementFactory->wrapElement('FileField', $field);
            }

            if ($isExternalFileField) {
                return $this->elementFactory->wrapElement('ExternalFileField', $field);
            }

            if ('datetime' === $type) {
                return $this->elementFactory->wrapElement('DateTimePicker', $field->getParent()->getParent());
            }

            if (in_array('custom-checkbox__input', $classes, true)) {
                return $this->elementFactory->wrapElement(
                    'PrettyCheckbox',
                    $field->getParent()->find('css', '.custom-checkbox__input')
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

            if ('textarea' === $field->getTagName()) {
                $wysiwyg = $field->getParent()->find('css', '.grapesjs');
                if ($wysiwyg) {
                    return $this->elementFactory->wrapElement('WysiwygField', $field);
                }
            }

            if ('orodigitalasset/js/app/views/digital-asset-choose-form-view'
                === (string) $field->getParent()->getAttribute('data-bound-view')) {
                return $this->elementFactory->wrapElement('DigitalAssetManagerField', $field);
            }

            return $field;
        }

        if (!$field && is_array($locator)) {
            self::fail(sprintf('Cannot find element by %s locator "%s"', $locator['type'], $locator['locator']));
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function findFieldByLabel($locator, $failOnError = true)
    {
        if ($label = $this->findLabel($locator)) {
            $sndParent = $label->getParent()->getParent();
            $classes = preg_split('/\s+/', (string)$sndParent->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);

            if (in_array('control-group-collection', $classes, true)) {
                $elementName = InflectorFactory::create()->singularize(trim($label->getText())).'Collection';
                $elementName = $this->elementFactory->hasElement($elementName) ? $elementName : 'CollectionField';

                return $this->elementFactory->wrapElement($elementName, $sndParent);
            } elseif (in_array('control-group-oro_file', $classes, true)) {
                $input = $sndParent->find('css', 'input[type="file"]');
                if ($input !== null) {
                    return $this->elementFactory->wrapElement('FileField', $input);
                }

                $input = $sndParent->find('css', 'input[data-is-external-file="1"]');

                return $this->elementFactory->wrapElement('ExternalFileField', $input);
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
        # Replace non-breaking space with regular space
        $value = str_replace("\xE2\x80\xAF", ' ', $value);

        if (0 === strpos($value, '[')) {
            $value = trim($value, '[]');
            return self::normalizeValue($value ? explode(',', $value) : []);
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
     * @throws BehatSecretsReaderException
     */
    protected static function checkAdditionalFunctions($value)
    {
        $matches = [];
        preg_match('/<(?P<function>[\w]+):(?P<value>.+)>/', $value, $matches);

        if (!empty($matches['function']) && !empty($matches['value'])) {
            if ('DateTime' === $matches['function']) {
                $value = (new \DateTime($matches['value']))->format(\DateTimeInterface::ATOM);
            }
            if ('Date' === $matches['function']) {
                switch ($matches['value']) {
                    case 'this month':
                        $dateValue = Carbon::today(new \DateTimeZone('UTC'));
                        $parsed = $dateValue->firstOfMonth();

                        return str_replace($matches[0], $parsed->format('M j, Y'), $value);
                    case 'this quarter':
                        $dateValue = Carbon::today(new \DateTimeZone('UTC'));
                        $parsed = $dateValue->firstOfQuarter();

                        return str_replace($matches[0], $parsed->format('M j, Y'), $value);
                    case 'this year':
                        $dateValue = Carbon::today(new \DateTimeZone('UTC'));
                        $parsed = $dateValue->firstOfYear();

                        return str_replace($matches[0], $parsed->format('M j, Y'), $value);
                    default:
                        $parsed = new \DateTime($matches['value']);
                        $value = str_replace($matches[0], $parsed->format('M j, Y'), $value);
                }
            }
            if ('Secret' === $matches['function']) {
                $value = BehatSecretsReader::getInstance()->getValue($matches['value']);
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

        if (!$errorSpan) {
            // Get the validation error a level higher than the input
            $errorSpan = $this->find(
                'xpath',
                sprintf(
                    '%s%s',
                    $field->getXpath(),
                    '/ancestor::div[contains(@class, "validation-error")]/span[@class="validation-failed"]'
                )
            );
        }

        if (!$errorSpan) {
            // Get the validation error when the input in the "div.fields-row"
            $errorSpan = $this->find(
                'xpath',
                sprintf(
                    '%s%s',
                    $field->getXpath(),
                    '/ancestor::div[contains(@class, "fields-row")]/following-sibling::span[@class="validation-failed"]'
                )
            );
        }

        if (!$errorSpan && $field->getAttribute('type') === 'file') {
            // Get the validation error for file input widget
            $errorSpan = $this->find(
                'xpath',
                sprintf(
                    '%s%s%s',
                    $field->getXpath(),
                    '/ancestor::div[contains(@class, "input-widget-file")]',
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
