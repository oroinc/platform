<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\UIBundle\Tests\Behat\Element\UiDialog;

class Select2Entity extends Element implements ClearableInterface
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->fillSearchField($value);
        $results = $this->getSuggestions();

        if (1 < count($results)) {
            // Try remove (Add new) records
            $results = array_filter($results, function (NodeElement $result) {
                return !stripos($result->getText(), 'Add new');
            });

            if (1 === count($results)) {
                array_shift($results)->click();
                $this->getDriver()->waitForAjax();
                return;
            }

            foreach ($results as $result) {
                if (trim($result->getText()) == $value) {
                    $result->click();
                    $this->getDriver()->waitForAjax();

                    return;
                }
            }

            self::fail(
                sprintf(
                    'Expected "%s" value, but got "%s" values',
                    $value,
                    implode(
                        ', ',
                        array_map(
                            function (NodeElement $result) {
                                return trim($result->getText());
                            },
                            $results
                        )
                    )
                )
            );
        }

        self::assertNotCount(0, $results, sprintf('Not found result for "%s"', $value));

        array_shift($results)->click();
        $this->getDriver()->waitForAjax();
    }

    /**
     * @param string $value
     */
    public function fillSearchField($value)
    {
        $this->open();
        /** @var NodeElement[] $inputs */
        $inputs = array_filter(
            $this->getPage()->findAll('css', '.select2-search input'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        );

        self::assertCount(1, $inputs);
        $this->getDriver()->typeIntoInput(array_shift($inputs)->getXpath(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($option, $multiple = false)
    {
        $this->setValue($option);
    }

    /**
     * @return NodeElement[]
     */
    public function getSuggestions()
    {
        $this->open();
        $this->getDriver()->waitForAjax();
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });
        $this->getDriver()->waitForAjax();

        /** @var NodeElement $resultSet */
        foreach ($this->getPage()->findAll('css', '.select2-results') as $resultSet) {
            if ($resultSet->isVisible()) {
                return $resultSet->findAll('css', 'li');
            }
        }

        return [];
    }

    /**
     * @return string[]
     */
    public function getSuggestedValues()
    {
        $suggestions = array_map(
            function (NodeElement $element) {
                return $element->getText();
            },
            $this->getSuggestions()
        );
        $this->close();

        return $suggestions;
    }

    public function open()
    {
        if (!$this->isOpen()) {
            $openArrow = $this->getParent()->find('css', '.select2-arrow');
            // Although ajax is already loaded element need some extra time to appear by js animation
            $openArrow->waitFor(60, function (NodeElement $element) {
                return $element->isVisible();
            });
            $openArrow->click();
        }
    }

    public function close()
    {
        if ($dropDownMask = $this->getPage()->find('css', '.select2-drop-mask')) {
            $dropDownMask->click();
        } elseif ($this->isOpen()) {
            $this->getParent()->find('css', '.select2-arrow')->click();
        }
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return 0 !== count(array_filter(
            $this->getPage()->findAll('css', '.select2-search'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        ));
    }

    /**
     * @param string $buttonName
     */
    public function openFromPlusButtonDropDown($buttonName)
    {
        $parent = $this->getParent()->getParent();
        $parent->find('css', '.entity-create-dropdown button')->click();

        // Get element again cause dom updated after dropdown is pressed
        $parent = $this->getPage()->find('xpath', $parent->getXpath());
        $parent->findButton($buttonName)->press();
    }

    /**
     * @return UiDialog
     */
    public function openSelectEntityPopup()
    {
        $this->getParent()->getParent()->find('css', '.entity-select-btn')->click();
        $this->getDriver()->waitForAjax();

        return $this->elementFactory->createElement('UiDialog');
    }

    public function clear()
    {
        $this->getParent()->find('css', '.select2-search-choice-close')->click();
    }

    /**
     * @return string|null
     */
    public function getChosenValue()
    {
        $span = $this->getParent()->find('css', 'span.select2-chosen');

        return $span ? $span->getText() : null;
    }
}
