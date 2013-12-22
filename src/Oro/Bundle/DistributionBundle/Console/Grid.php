<?php
namespace Oro\Bundle\DistributionBundle\Console;

class Grid
{
    /**
     * @var int
     */
    protected $countColumns;
    /**
     * @var array
     */
    protected $delimiters;

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var string
     */
    protected $defaultDelimiter;

    /**
     * @var string
     */
    protected $formatString;


    /**
     * @param int $countColumns
     * @param array $delimiters
     */
    public function __construct($countColumns, array $delimiters = [])
    {
        $this->rows = [];
        $this->defaultDelimiter = ':';
        $this->formatString = '';

        $this->countColumns = $countColumns;
        $delimiters = array_slice($delimiters, 0, $this->countColumns - 1);
        $this->delimiters = array_pad($delimiters, $this->countColumns - 1, $this->defaultDelimiter);

        for ($i = 0; $i < $this->countColumns; $i++) {
            $placeholder = '_' . ($i) . '_';
            $this->formatString .= '%' . $placeholder . '.' . $placeholder . 's';

            if ($i !== $this->countColumns - 1) {
                $this->formatString .= ' ' . $this->delimiters[$i] . ' ';
            }
        }

    }

    /**
     * @param array $row
     */
    public function addRow(array $row)
    {
        $row = array_slice($row, 0, $this->countColumns);
        $row = array_pad($row, $this->countColumns, '');
        $this->rows[] = $row;
    }

    /**
     * @return string
     */
    public function render()
    {
        $formatString = $this->createFormatString();
        $result = [];
        foreach ($this->rows as $row) {
            $result[] = vsprintf($formatString, $row);
        }

        return implode(PHP_EOL, $result);
    }

    /**
     * @return array
     */
    protected function createFormatString()
    {
        $maxLengths = array_fill(0, $this->countColumns, 0);
        foreach ($this->rows as $row) {
            for ($i = 0; $i < $this->countColumns; $i++) {
                $maxLengths[$i] = max($maxLengths[$i], strlen($row[$i]));
            }
        }
        $formatString = $this->formatString;
        foreach ($maxLengths as $k => $v) {
            $formatString = str_replace('_' . $k . '_', $v, $formatString);
        }
        return $formatString;
    }
}
