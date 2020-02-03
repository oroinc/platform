<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class GridColumnManager extends Element
{
    /**
     * Represents parent grid
     * @var Element|null
     */
    private $grid;

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

        $this->uncheckVisibility($visibilityCheckbox);
    }

    /**
     * @param NodeElement $visibilityCheckbox
     */
    private function uncheckVisibility(NodeElement $visibilityCheckbox)
    {
        $this->ensureManagerVisible();

        if (!$visibilityCheckbox->isChecked()) {
            return;
        }

        $visibilityCheckbox->click();

        self::assertFalse(
            $visibilityCheckbox->isChecked(),
            'Can not uncheck visibility checkbox'
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

            $this->uncheckVisibility($this->getVisibilityCheckboxFromRow($row));
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

        $close = $this->find('css', '.close');
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

        // Find elements in parent grid if it is set otherwise elements will be created in element factory
        if ($this->grid) {
            $gridSettingsButton = $this->grid->getElement('GridColumnManagerButton');
            $tabTitle = $this->grid->getElement('GridColumnManagerTabTitle');
        } else {
            $gridSettingsButton = $this->elementFactory->createElement('GridColumnManagerButton');
            $tabTitle = $this->elementFactory->createElement('GridColumnManagerTabTitle');
        }

        $gridSettingsButton->click();
        $tabTitle->click();

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

        $visibilityCheckbox = $this->getVisibilityCheckboxFromRow($tableRow);

        self::assertNotNull($visibilityCheckbox, 'Can not find visibility cell for ' . $title);

        return $visibilityCheckbox;
    }

    /**
     * @param TableRow $tableRow
     *
     * @return NodeElement|null
     */
    private function getVisibilityCheckboxFromRow(TableRow $tableRow): ?NodeElement
    {
        $visibilityCheckbox = $tableRow->find('css', '.visibility-cell input[type=checkbox]');

        return $visibilityCheckbox;
    }

    /**
     * @param Element $grid
     * @return $this
     */
    public function setGrid(Element $grid): self
    {
        $this->grid = $grid;

        return $this;
    }
}
