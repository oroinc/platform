<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\VariableStorage;

class MultipleChoice extends AbstractGridFilterItem
{
    /**
     * @param array $values Array of checkbox labels for check/uncheck
     */
    public function checkItems(array $values)
    {
        $this->open();
        $widget = $this->getWidget();
        $inputs = $widget->findAll('css', '.multiselect__item > label');

        foreach ($values as $value) {
            $value = VariableStorage::normalizeValue($value);
            $item = $this->findElementByText($inputs, $value);

            self::assertNotNull($item, sprintf('Could not find checkbox with "%s" text', $value));

            $item->click();
            $this->getDriver()->waitForAjax();
        }

        // Hide dropdown menu, because of #oro-dropdown-mask cover all page
        $this->close();
    }

    /**
     * @param string $filterItems
     */
    public function checkItemsInFilter($filterItems)
    {
        $filterItems = array_map('trim', explode(',', $filterItems));

        $this->checkItems($filterItems);
    }

    /**
     * @param string $filterItems
     */
    public function checkItemsInFilterStrict($filterItems)
    {
        $filterItems = array_map('trim', explode(',', $filterItems));

        $this->checkItemsStrict($filterItems);
    }

    /**
     * @param array $values Array of checkbox labels(case-sensitive) for check/uncheck
     */
    public function checkItemsStrict(array $values)
    {
        $this->open();
        $widget = $this->getWidget();
        $inputs = $widget->findAll('css', '.multiselect__item > label');

        foreach ($values as $value) {
            $value = VariableStorage::normalizeValue($value);
            $item = $this->findElementByTextStrict($inputs, $value);

            self::assertNotNull($item, sprintf('Could not find checkbox with "%s" text', $value));

            $item->click();
            $this->getDriver()->waitForAjax();
        }

        // Hide dropdown menu, because of #oro-dropdown-mask cover all page
        $this->close();
    }

    /**
     * @param string $value checkbox label to verify
     * @return bool
     */
    public function isItemChecked(string $value): bool
    {
        $value = VariableStorage::normalizeValue($value);
        $this->open();
        $widget = $this->getWidget();
        $inputs = $widget->findAll('css', '.multiselect__item > label');
        $item = $this->findElementByText($inputs, $value);

        self::assertNotNull($item, sprintf('Could not find checkbox with "%s" text', $value));
        $checkbox = $item->find('css', 'input');
        $isChecked = $checkbox->isChecked();
        $this->close();

        return $isChecked;
    }

    /**
     * Get visible miltiselect checkboxes widget.
     * There are only one visible widget can be on the page
     *
     * @return NodeElement
     */
    protected function getWidget()
    {
        $widgets = $this->getPage()->findAll('css', 'body div.filter-container .multiselect-view');

        /** @var NodeElement $widget */
        foreach ($widgets as $widget) {
            if ($widget->isVisible()) {
                return $widget;
            }
        }

        self::fail('Could not find widget on page or it\'s not visible');
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
        $foundItem = null;
        foreach ($items as $item) {
            if (strtolower($item->getText()) === strtolower($text)) {
                $foundItem = $item;
                break;
            }
            if (null === $foundItem && stripos($item->getText(), $text) !== false) {
                $foundItem = $item;
            }
        }

        return $foundItem;
    }

    /**
     * Find element by text (case-sensitive)
     *
     * @param NodeElement[] $items
     * @param string $text Searched text in elements
     *
     * @return NodeElement|null
     */
    protected function findElementByTextStrict($items, $text)
    {
        $foundItem = null;
        foreach ($items as $item) {
            if ($item->getText() === $text) {
                $foundItem = $item;
                break;
            }
            if (null === $foundItem && str_contains($item->getText(), $text)) {
                $foundItem = $item;
            }
        }

        return $foundItem;
    }

    #[\Override]
    public function open()
    {
        parent::open();
        // Wait for open widget
        $this->getDriver()->waitForAjax();
    }

    #[\Override]
    public function close()
    {
        $dropDownMask = $this->getPage()->find('css', '.oro-dropdown-mask');

        if ($dropDownMask && $dropDownMask->isVisible()) {
            $dropDownMask->click();
        } elseif ($this->isOpen()) {
            $this->find('css', '.filter-item.open-filter')->click();
        }
    }
}
