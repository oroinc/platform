<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\UIBundle\Tests\Behat\Element\UiDialog;
use WebDriver\Key;

class Select2Entity extends Element implements ClearableInterface
{
    /**
     * @var int Count of attempts for getting the correct field suggestions
     */
    protected $attempts;

    /**
     * @var string
     */
    protected $searchInputSelector = '.select2-search input';

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
        $this->getDriver()->typeIntoInput($this->getSearchInput()->getXpath(), $value);
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
        $this->attempts = 0;
        $resultSet = $this->getResultSet();

        $results = $this->spin(function (Select2Entity $element) use ($resultSet) {
            /** @var NodeElement[] $results */
            $results = $resultSet->findAll('css', 'li');
            if (3 == $element->attempts) {
                return $results;
            }

            try {
                foreach ($results as $result) {
                    $result->isVisible();
                }
            } catch (\Exception $e) {
                $element->attempts = 0;
            }

            $element->attempts++;

            return [];
        }, 5);

        return $results;
    }

    /**
     * @return NodeElement
     */
    public function getResultSet()
    {
        $this->open();
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });

        /** @var NodeElement $resultSet */
        foreach ($this->getPage()->findAll('css', '.select2-results') as $resultSet) {
            if ($resultSet->isVisible()) {
                return $resultSet;
            }
        }

        self::fail('No select 2 entity results found on page');
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
            if ($openArrow) {
                $this->focus();
                // Although ajax is already loaded element need some extra time to appear by js animation
                $openArrow->waitFor(60, function (NodeElement $element) {
                    return $element->isVisible();
                });
                if ($openArrow->isVisible()) {
                    try {
                        $openArrow->click();
                    } catch (\Exception $e) {
                        // Some elements on the page may be covered by sticky panel.
                        // We should scroll up to the page logo. I will allow to click on the element.
                        $this->getPage()->find('css', '.logo')->mouseOver();
                        $openArrow->click();
                    }
                }
            }
        }
    }

    public function close()
    {
        if ($this->getPage()->has('css', '.select2-drop-mask')) {
            $this->getDriver()->typeIntoInput($this->getSearchInput()->getXpath(), Key::ESCAPE);
        } elseif ($this->isOpen()) {
            $this->getParent()->find('css', '.select2-arrow')->click();
        }
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->spin(function () {
            return 0 !== count(array_filter(
                $this->getPage()->findAll('css', '.select2-search'),
                function (NodeElement $element) {
                    return $element->isVisible();
                }
            ));
        }, 5);
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
     * @param bool $force
     * @return UiDialog
     */
    public function openSelectEntityPopup($force = false)
    {
        $entitySelect = $this->getParent()->getParent();
        $entitySelectButton = $entitySelect->find('css', '.entity-select-btn');
        $this->spin(function () use ($entitySelect) {
            return $entitySelect->find('css', '.select2-container');
        }, 10);
        $entitySelectButton->focus();
        if ($entitySelectButton->isVisible()) {
            if ($force) {
                $this->getDriver()->executeJsOnXpath($entitySelectButton->getXpath(), '{{ELEMENT}}.click()');
            } else {
                $entitySelectButton->click();
            }
            $this->getDriver()->waitForAjax();

            return $this->elementFactory->createElement('UiDialog');
        }

        return null;
    }

    public function clear()
    {
        $this->getParent()->find('css', '.select2-search-choice-close')->click();
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        $span = $this->getParent()->find('css', 'span.select2-chosen');

        return $span ? $span->getText() : null;
    }

    /**
     * @return NodeElement
     */
    private function getSearchInput()
    {
        $inputs = array_filter(
            $this->getPage()->findAll('css', $this->searchInputSelector),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        );

        self::assertCount(1, $inputs);

        return array_shift($inputs);
    }
}
