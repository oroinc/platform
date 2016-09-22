<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Select2Entity extends Element
{
    public function setValue($value)
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
        array_shift($inputs)->setValue($value);

        $results = $this->getSuggestions();

        if (1 < count($results)) {
            foreach ($results as $result) {
                if ($result->getText() == $value) {
                    $result->click();

                    return;
                }
            }

            self::fail(sprintf('Too many results for "%s"', $value));
        }

        self::assertNotCount(0, $results, sprintf('Not found result for "%s"', $value));

        array_shift($results)->click();
        $this->getDriver()->waitForAjax();
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
            $this->getParent()->find('css', '.select2-arrow')->click();
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
}
