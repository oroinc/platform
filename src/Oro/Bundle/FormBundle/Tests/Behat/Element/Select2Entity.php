<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\UIBundle\Tests\Behat\Element\UiDialog;
use WebDriver\Exception\StaleElementReference;
use WebDriver\Key;

/**
 * Select control with autocomplete functionality
 * uses in fields that represent many-to-one relationship.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Select2Entity extends Element implements ClearableInterface
{
    /**
     * @var int Count of attempts for getting the correct field suggestions
     */
    protected $attempts;

    /**
     * @var string
     */
    protected $searchInputSelector = '.select2-drop-active .select2-input,.select2-dropdown-open .select2-input';

    /**
     * Select2 is attached to a hidden input which is not visible.
     * Use parent container to check element visibility.
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->getParent()->isVisible();
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        if (empty($value)) {
            if (!$this->isEmptyValue()) {
                $this->clear();
            }

            return;
        }

        $results = $this->getSuggestions($value);

        if (1 < count($results)) {
            // Try remove (Add new) records
            $results = array_filter($results, function (NodeElement $result) {
                $text = $this->spin(function () use ($result) {
                    return $result->getText();
                });

                return !stripos($text, 'Add new');
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
    public function fillSearchField($value, bool $failOnEmpty = true)
    {
        $this->open();
        $searchInput = $this->getSearchInput($failOnEmpty);
        if ($searchInput) {
            $this->getDriver()->typeIntoInput($searchInput->getXpath(), $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($option, $multiple = false)
    {
        $this->setValue($option);
    }

    /**
     * @param string $value
     * @return NodeElement[]
     */
    public function getSuggestions($value = '')
    {
        $this->attempts = 0;
        $resultSet = $this->getResultSet(true, $value);

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
     * @param Session $session
     * @param string $value
     * @return mixed|null
     */
    public function getAllSuggestions(Session $session, $value = '')
    {
        $this->attempts = 0;
        $resultSet = $this->getResultSetFromAllPages($session, true, $value);

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
     * @param bool $failOnEmpty
     * @param string $value
     * @return NodeElement|null
     */
    public function getResultSet($failOnEmpty = true, $value = '')
    {
        $this->fillSearchField($value, $failOnEmpty);
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });

        /** @var NodeElement $resultSet */
        foreach ($this->getPage()->findAll('css', '.select2-results') as $resultSet) {
            if ($resultSet->isVisible()) {
                return $resultSet;
            }
        }

        if ($failOnEmpty) {
            self::fail('No select 2 entity results found on page');
        }

        return null;
    }

    public function getResultSetFromAllPages(Session $session, $failOnEmpty = true, $value = '')
    {
        $this->fillSearchField($value);
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });

        $function = <<<JS
            (function(){
                 var activeResultContainer = $('.select2-drop-active .select2-results').get(0);
                 activeResultContainer.scrollTo(0,activeResultContainer.scrollHeight);
            })()
JS;

        while (null !== $this->getPage()->find('css', '.select2-more-results')) {
            $session->executeScript($function);
            $this->waitFor(60, function () {
                return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
            });
        }

        /** @var NodeElement $resultSet */
        foreach ($this->getPage()->findAll('css', '.select2-results') as $resultSet) {
            if ($resultSet->isVisible()) {
                return $resultSet;
            }
        }

        if ($failOnEmpty) {
            self::fail('No select 2 entity results found on page');
        }

        return null;
    }

    /**
     * @param string $value
     * @return string[]
     */
    public function getSuggestedValues($value = '')
    {
        $suggestions = array_map(
            function (NodeElement $element) {
                return $element->getText();
            },
            $this->getSuggestions($value)
        );
        $this->close();

        return $suggestions;
    }

    public function open()
    {
        $this->openElement();
    }

    /**
     * @param bool $retryOnStaleElement
     * @throws StaleElementReference
     */
    protected function openElement($retryOnStaleElement = true)
    {
        try {
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
        } catch (StaleElementReference $e) {
            if ($retryOnStaleElement) {
                $this->spin(function () {
                    return $this->openElement(false);
                }, 5);
            } else {
                throw $e;
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
        return 0 !== count(
            array_filter(
                $this->getPage()->findAll('css', '.select2-search'),
                function (NodeElement $element) {
                    return $element->isVisible();
                }
            )
        );
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

            $dialogs = array_filter(
                $this->elementFactory->findAllElements('UiDialog'),
                function (UiDialog $dialog) {
                    return $dialog->isValid() && $dialog->isVisible();
                }
            );
            if ($dialogs) {
                return end($dialogs);
            }
        }

        return null;
    }

    /**
     * @param bool $force
     * @return UiDialog
     */
    public function openCreateEntityPopup($force = false)
    {
        $entitySelect = $this->getParent()->getParent();
        $entityCreateButton = $entitySelect->find('css', '.entity-create-btn');
        $this->spin(function () use ($entitySelect) {
            return $entitySelect->find('css', '.select2-container');
        }, 10);
        $entityCreateButton->focus();
        if ($entityCreateButton->isVisible()) {
            if ($force) {
                $this->getDriver()->executeJsOnXpath($entityCreateButton->getXpath(), '{{ELEMENT}}.click()');
            } else {
                $entityCreateButton->click();
            }
            $this->getDriver()->waitForAjax();

            $dialogs = array_filter(
                $this->elementFactory->findAllElements('UiDialog'),
                function (UiDialog $dialog) {
                    return $dialog->isValid() && $dialog->isVisible();
                }
            );
            if ($dialogs) {
                return end($dialogs);
            }
        }

        return null;
    }

    public function clear()
    {
        $close = $this->getParent()->find('css', '.select2-search-choice-close');
        if ($close && $close->isVisible()) {
            $close->click();
        }
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
     * @return bool
     */
    public function isEmptyValue()
    {
        return !$this->getParent()->find(
            'xpath',
            '//a[contains(@class, "select2-choice") and not(contains(@class, "select2-default"))]'
            . '//span[@class="select2-chosen"]'
        );
    }

    /**
     * @param bool $failOnEmpty
     * @return NodeElement|null
     */
    private function getSearchInput(bool $failOnEmpty = true)
    {
        $inputs = array_filter(
            $this->getPage()->findAll('css', $this->searchInputSelector),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        );

        if ($failOnEmpty) {
            self::assertCount(1, $inputs);
        }

        if ($inputs) {
            return array_shift($inputs);
        }

        return null;
    }
}
