<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class GridColumnManager extends Element
{
    /**
     * @param string $title
     */
    public function checkColumnVisibility($title)
    {
        $this->ensureManagerVisible();

        $visibilityCell = $this->getVisibilityCheckbox($title);

        if ($visibilityCell->isChecked()) {
            return;
        }

        $visibilityCell->click();

        self::assertTrue($visibilityCell->isChecked(), 'Can not check visibility checkbox for ' . $title . ' column');
    }

    /**
     * @param string $title
     */
    public function uncheckColumnVisibility($title)
    {
        $this->ensureManagerVisible();

        $visibilityCheckbox = $this->getVisibilityCheckbox($title);

        if (!$visibilityCheckbox->isChecked()) {
            return;
        }

        $visibilityCheckbox->click();

        self::assertFalse(
            $visibilityCheckbox->isChecked(),
            'Can not uncheck visibility checbox for ' . $title . ' column'
        );
    }

    /**
     * Hide all columns in grid exception mentioned in exceptions array
     *
     * @param array $exceptions
     */
    public function hideAllColumns(array $exceptions = [])
    {
        $this->ensureManagerVisible();

        $rows = $this->getColumnManagerTable()->getRows();
        foreach ($rows as $row) {
            $name = $row->getCellValue('Name');

            // Skip exceptions
            if (in_array($name, $exceptions, true)) {
                continue;
            }

            $this->uncheckColumnVisibility($name);
        }
    }

    public function open()
    {
        $this->ensureManagerVisible();
    }

    public function close()
    {
        if (!$this->isVisible()) {
            return;
        }

        $close = $this->find('css', 'span.close');
        $close->click();
    }

    /**
     * @return Table
     */
    protected function getColumnManagerTable()
    {
        return $this->elementFactory->createElement('GridColumnManagerTable');
    }

    protected function ensureManagerVisible()
    {
        if ($this->isVisible()) {
            return;
        }

        $button = $this->elementFactory->createElement('GridColumnManagerButton');
        $button->click();

        self::assertTrue($this->isVisible(), 'Can not open grid column manager dropdown');
    }

    /**
     * @param string $title
     * @return NodeElement|mixed|null
     */
    protected function getVisibilityCheckbox($title)
    {
        $columnManagerTable = $this->getColumnManagerTable();
        $tableRow = $columnManagerTable->getRowByContent($title);

        $visibilityCheckbox = $tableRow->find('css', '.visibility-cell input[type=checkbox]');

        self::assertNotNull($visibilityCheckbox, 'Can not find visibility cell for ' . $title);

        return $visibilityCheckbox;
    }
}
