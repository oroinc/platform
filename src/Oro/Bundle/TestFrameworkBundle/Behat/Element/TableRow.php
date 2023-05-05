<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;

/**
 * Table Row element representation
 */
class TableRow extends Element
{
    const HEADER_ELEMENT = 'TableHeader';

    /**
     * @var Table
     */
    protected $owner;

    /**
     * @param int $number Row index number starting from 0
     * @return NodeElement
     */
    public function getCellByNumber($number)
    {
        $number = (int) $number;
        $columns = $this->findAll('xpath', 'child::td|child::th');
        self::assertArrayHasKey($number, $columns);

        return $columns[$number];
    }

    /**
     * @param string $header
     * @return NodeElement
     */
    public function getCellByHeader($header)
    {
        $columnNumber = $this->getColumnNumberByHeader($header);

        return $this->getCellByNumber($columnNumber);
    }

    /**
     * @param string $header Column header name
     * @return \DateTime|int|string
     */
    public function getCellValue($header)
    {
        $columnNumber = $this->getColumnNumberByHeader($header);

        return self::normalizeValueByGuessingType(
            $this->getCellElementValue($columnNumber)
        );
    }

    public function getCellValues(array $headers): array
    {
        $values = [];
        foreach ($headers as $header) {
            $values[] = $this->getCellValue($header);
        }

        return $values;
    }

    public function setOwner(Table $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return Table
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Try to guess type of value and return that data in that type
     * @param string $value
     * @return \DateTime|int|string
     */
    public static function normalizeValueByGuessingType($value)
    {
        $value = trim($value);

        if (empty($value)) {
            return $value;
        }

        if (preg_match('/^-?[0-9]+$/', $value)) {
            return (int) $value;
        } elseif (preg_match('/^\p{Sc}(?P<amount>[0-9]+)$/u', $value, $matches)) {
            return (int) $matches['amount'];
        } elseif (($date = date_create($value, new \DateTimeZone('UTC'))) &&
            (
                preg_match(
                    '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)\s([1-3])?[0-9]\,\s[1-2][0-9]{3}/',
                    $value
                ) ||
                preg_match(
                    '/^[1-2][0-9]{3}-[0-1][0-9]-[0-3][0-9]$/',
                    $value
                )
            )
        ) {
            return $date;
        }

        return $value;
    }

    /**
     * @param int $columnNumber
     * @return string
     */
    protected function getCellElementValue($columnNumber)
    {
        $cellElement = $this->getCellByNumber($columnNumber);
        $input = $cellElement->find('css', 'input');
        $cellElementValue = $cellElement->getText();

        // if it's simple element, just return text
        if (!$input) {
            return $cellElementValue;
        }

        // if it's a checkbox, use 'checked' attribute rather than text value
        if ($input->hasAttribute('type') && 'checkbox' === $input->getAttribute('type')) {
            $cellElementValue = (int) $input->isChecked();
        } elseif (empty($cellElementValue)) {
            // no text in cell element, but there's an input element — take its value
            $cellElementValue = $input->getValue();
        }

        return $cellElementValue;
    }

    /**
     * @param string $header
     * @return int
     */
    private function getColumnNumberByHeader($header)
    {
        if ($this->owner) {
            $tableHeader = $this->owner->getHeader();
        } else {
            /** @var TableHeader $tableHeader */
            $tableHeader = $this->elementFactory
                ->createElement(static::HEADER_ELEMENT, $this->getParent()->getParent());
        }

        return $tableHeader->getColumnNumber($header);
    }
}
