<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Select2Entity extends Element
{
    public function setValue($value)
    {
        $this->open();

        /** @var NodeElement $input */
        foreach ($this->findAll('xpath', '//div[contains(@class, "select2-search")]/input') as $input) {
            if ($input->isVisible()) {
                $input->setValue($value);
            }
        }

        $this->getDriver()->waitForAjax();
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });

        $results = $this->getSuggestedValues();
        if (1 < count($results)) {
            /** @var NodeElement $result */
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

//    public function getSuggestedValues()
//    {
//        $this->open();
//        $this->waitFor(60, function () {
//            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
//        });
//
//        return $this->getPage()->findAll('css', '.select2-results li');
//    }

    /**
     * @return string[]
     */
    public function getSuggestedValues()
    {
        $this->open();
        $this->waitFor(60, function () {
            return null === $this->getPage()->find('css', '.select2-results li.select2-searching');
        });

//        $resultsHoldersXpaths = [
//            '//ul[contains(@class, "select2-result-sub")]',
//            '//ul[contains(@class, "select2-result")]',
//        ];
//
//        while ($resultsHoldersXpath = array_shift($resultsHoldersXpaths)) {
//            /** @var NodeElement $element */
//            foreach ($this->findAll('xpath', $resultsHoldersXpath) as $element) {
//                if ($element->isVisible()) {
//                    return $element->findAll('css', 'li');
//                }
//            }
//        }
//
//        return [];
        return $this->getPage()->findAll('css', '.select2-results li');
    }

    public function open()
    {
        if (!$this->isOpen()) {
            $this->getParent()->find('css', '.select2-arrow')->click();
        }
    }

    public function close()
    {
        if ($this->isOpen()) {
            $this->getParent()->find('css', '.select2-arrow')->click();
        }
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return 0 !== count(array_filter(
            $this->getPage()->findAll('css', 'select2-search'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        ));
    }
}
