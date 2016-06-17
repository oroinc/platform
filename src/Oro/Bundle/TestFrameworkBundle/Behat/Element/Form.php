<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Behat\Tester\Exception\PendingException;
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

            $field = $this->findField($locator);

            if (null === $field) {
                throw new ElementNotFoundException(
                    $this->getDriver(),
                    'form field',
                    'id|name|label|value|placeholder',
                    $locator
                );
            }

            switch ($field->getTagName()) {
                case 'input':
                    $this->fillAsInput($field, $row[1]);
                    break;
                case 'textarea':
                    $this->fillAsTextarea($field, $row[1]);
                    break;
                default:
                    throw new PendingException('Filled this fild is not implemented yet');
            }
        }
    }

    /**
     * @param NodeElement $element
     * @param string $value
     */
    protected function fillAsInput(NodeElement $element, $value)
    {
        $type = $element->getAttribute('type');
        if ($element->hasClass('select2-offscreen')) {
            $this->setSelect2Input($element, $value);
        } elseif ('text' === $type) {
            $element->setValue(null);
            $element->setValue($value);
        } else {
            throw new PendingException(sprintf('Type "%s" input is not implemented yet', $type));
        }
    }

    /**
     * @param NodeElement $element
     * @param string $value
     */
    protected function setSelect2Input(NodeElement $element, $value)
    {
        throw new PendingException('Fill select2 input is not implemented yet');
    }

    /**
     * @param NodeElement $element
     * @param string $value
     */
    protected function fillAsTextarea(NodeElement $element, $value)
    {
        if ('true' === $element->getAttribute('aria-hidden')) {
            $this->fillAsTinyMce($element, $value);
            return;
        }

        $element->setValue($value);
    }

    /**
     * @param NodeElement $element
     * @param string $value
     */
    protected function fillAsTinyMce(NodeElement $element, $value)
    {
        $fieldId = $element->getAttribute('id');

        $isTinyMce = $this->getDriver()->evaluateScript(
            sprintf('null != tinyMCE.get("%s");', $fieldId)
        );

        if (!$isTinyMce) {
            throw new PendingException(
                sprintf('Field was guessed as tinymce, but can\'t find tiny with id "%s" on page', $fieldId)
            );
        }

        $this->getDriver()->executeScript(
            sprintf('tinyMCE.get("%s").setContent("%s");', $fieldId, $value)
        );
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
     * @param $text
     * @throws ElementNotFoundException
     */
    public function fillSelect2Search($text)
    {
        $this->getDriver()->executeScript(sprintf("
            var select2input = $('input.select2-input');

            select2input.val('%s');
            select2input.trigger({type: 'keyup-change'});
        ", $text));
    }

    /**
     * @return int
     */
    public function getSelect2ResultsCount()
    {
        $this->waitForSelect2SearchResults();

        return count($this->getPage()->findAll('css', '.select2-results li'));
    }

    /**
     * @param string $text
     * @throws ExpectationException
     */
    public function pressTextInSearchResult($text)
    {
        $this->waitForSelect2SearchResults();
        /** @var NodeElement $resultItem */
        foreach ($this->getPage()->findAll('css', '.select2-results li') as $resultItem) {
            if (preg_match(sprintf('/%s/', $text), $resultItem->getText())) {
                $resultItem->click();
                return;
            }
        }

        throw new ExpectationException(sprintf('Can\'t find resut with text "%s"', $text), $this->getDriver());
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

    protected function waitForSelect2SearchResults()
    {
        $searchInProgress = <<<JS
            $('li.select2-searching').length == 0;
JS;
        $this->getDriver()->wait(5000, $searchInProgress);
    }
}
