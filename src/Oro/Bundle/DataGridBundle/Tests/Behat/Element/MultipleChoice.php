<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\AbstractGridFilterItem;

class MultipleChoice extends AbstractGridFilterItem
{
    /**
     * @param array $values Array of checkbox labels for check/uncheck
     */
    public function checkItems(array $values)
    {
        $this->open();
        // Wait for open widget
        $this->getDriver()->waitForAjax();
        $widget = $this->getWidget();
        $inputs = $widget->findAll('css', 'li label');

        foreach ($values as $value) {
            $item = $this->findElementByText($inputs, $value);

            self::assertNotNull($item, sprintf('Cann\'t find checkbox with "%s" text', $value));

            $item->click();
            $this->getDriver()->waitForAjax();
        }

        // Hide dropdown menu, because of #oro-dropdown-mask cover all page
        $this->close();
    }

    /**
     * Get visible miltiselect checkboxes widget.
     * There are only one visible widget can be on the page
     *
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getWidget()
    {
        $widgets = $this->getPage()->findAll('css', 'body div.select-filter-widget ul.ui-multiselect-checkboxes');

        /** @var NodeElement $widget */
        foreach ($widgets as $widget) {
            if ($widget->isVisible()) {
                return $widget;
            }
        }

        self::fail('Can\'t find widget on page or it\'s not visible');
    }

    /**
     * Find element by text
     *
     * @param NodeElement[] $items
     * @param string $text Searched text in elements
     *
     * @return NodeElement|null
     */
    protected function findElementByText($items, $text)
    {
        /** @var NodeElement $input */
        foreach ($items as $input) {
            if (preg_match(sprintf('/%s/i', $text), $input->getText())) {
                return $input;
            }
        }

        return null;
    }

    public function close()
    {
        if ($dropDownMask = $this->getPage()->find('css', '.oro-dropdown-mask')) {
            $dropDownMask->click();
        }
    }
}
