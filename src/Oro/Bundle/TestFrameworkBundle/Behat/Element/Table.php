<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Table element representation
 */
class Table extends Element
{
    const TABLE_HEADER_ELEMENT = 'TableHeader';
    const TABLE_ROW_STRICT_ELEMENT = 'TableRowStrict';
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
     * @param bool $failIfNotFound
     * @return TableRow tr element of table
     */
    public function getRowByContent($content, $failIfNotFound = true)
    {
        return $this->getRowByContentElement($content, static::TABLE_ROW_ELEMENT, $failIfNotFound);
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
        return $this->getRowElements(static::TABLE_ROW_STRICT_ELEMENT);
    }

    /**
     * @param string $elementName
     * @return TableRow[]
     */
    public function getRowElements($elementName)
    {
        return array_map(function (TableRow $row) {
            $row->setOwner($this);

            return $row;
        }, $this->getElements($elementName));
    }

    /**
     * @param string $content Any content that can identify row
     * @param string $elementName
     * @param bool $failIfNotFound
     * @return TableRow|null
     */
    public function getRowByContentElement($content, $elementName, $failIfNotFound = true)
    {
        /** @var TableRow|null $row */
        $row = $this->spin(function () use ($elementName, $content) {
            $element = $this->findElementContains($elementName, $content);

            return $element->isIsset() ? $element : false;
        }, 2);

        if ($failIfNotFound) {
            self::assertNotNull($row, sprintf(static::ERROR_NO_ROW_CONTENT, $content));
        }
        if ($row) {
            $row->setOwner($this);
        }

        return $row;
    }

    /**
     * @return TableHeader
     */
    public function getHeader()
    {
        return $this->getHeaderElement(static::TABLE_HEADER_ELEMENT);
    }

    /**
     * @param string $elementName
     * @return TableHeader
     */
    public function getHeaderElement($elementName)
    {
        return $this->elementFactory->wrapElement(
            $elementName,
            $this->find('xpath', 'child::thead/child::tr')
        );
    }
}
