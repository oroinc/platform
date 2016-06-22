<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\WaitingDictionary;
use WebDriver\Element as WdElement;
use WebDriver\Session as WdSession;

class Form extends Element
{
    use WaitingDictionary;

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
     *
     * @todo move to OroSelenium2Driver BAP-10843
     */
    protected function fillAsInput(NodeElement $element, $value)
    {
        if ($element->hasClass('select2-offscreen')) {
            $this->setSelect2Input($element, $value);
        } else {
            /** @var Selenium2Driver $driver */
            $driver = $this->getDriver();
            $wdElement = $driver->getWebDriverSession()->element('xpath', $element->getXpath());
            $script = <<<JS
var node = {{ELEMENT}};
node.value = '$value';
JS;

            $this->executeJsOnElement($wdElement, $script);
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
        $this->waitForAjax();

        return count($this->getPage()->findAll('css', '.select2-results li'));
    }

    /**
     * @param string $text
     * @throws ExpectationException
     */
    public function pressTextInSearchResult($text)
    {
        $this->waitForAjax();
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

    private function executeJsOnElement(WdElement $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);
        /** @var WdSession $wdSession */
        $wdSession = $this->getDriver()->getWebDriverSession();

        $options = array(
            'script' => $script,
            'args'   => array(array('ELEMENT' => $element->getID())),
        );

        if ($sync) {
            return $wdSession->execute($options);
        }

        return $wdSession->execute_async($options);
    }
}
