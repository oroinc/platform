<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;

class Table extends Element
{
    const TABLE_HEADER_ELEMENT = 'TableHeader';
    const TABLE_ROW_ELEMENT = 'TableRow';
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in table";
    const ERROR_NO_ROW_CONTENT = 'Table has no record with "%s" content';

    /**
     * Get Element tr by row number
     *
     * @param int $rowNumber Number of table record starting from 1
     * @return TableRow tr element of grid
     */
    public function getRowByNumber($rowNumber)
    {
        $rowIndex = $rowNumber - 1;
        $rows = $this->getRows();

        self::assertArrayHasKey($rowIndex, $rows, sprintf(static::ERROR_NO_ROW, $rowNumber, count($rows)));

        return $rows[$rowIndex];
    }

    /**
     * Get Element tr by row content
     *
     * @param string $content Any content that can identify row
     * @return TableRow tr element of table
     */
    public function getRowByContent($content)
    {
        /** @var TableRow $row */
        $row = $this->findElementContains(static::TABLE_ROW_ELEMENT, $content);
        self::assertTrue($row->isIsset(), sprintf(static::ERROR_NO_ROW_CONTENT, $content));
        return $row;
    }

    public function assertNoRecords()
    {
        self::assertCount(0, $this->getRows());
    }

    /**
     * @return TableRow[]
     */
    public function getRows()
    {
        return array_map(function (NodeElement $element) {
            return $this->elementFactory->wrapElement(static::TABLE_ROW_ELEMENT, $element);
        }, $this->findAll('css', 'tbody tr'));
    }
}
