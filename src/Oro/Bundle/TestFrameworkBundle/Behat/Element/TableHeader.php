<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Symfony\Component\DomCrawler\Crawler;

/**
 * This class represents table header in behat contexts
 */
class TableHeader extends Element
{
    /**
     * Try to guess header and return column number
     *
     * @param string $headerText Header of table column
     * @return int column number
     */
    public function getColumnNumber($headerText)
    {
        $crawler = new Crawler($this->getHtml());

        $i = 0;
        $headers = [];

        /** @var \DOMElement $th */
        foreach ($crawler->filter('th') as $th) {
            $currentHeader = preg_replace('/[\\n\\r]+/', ' ', trim($th->textContent));
            if (strtolower($currentHeader) === strtolower($headerText)) {
                return $i;
            }

            $i++;
            $headers[] = $currentHeader;
        }

        self::fail(sprintf(
            'Can\'t find link with "%s" header, available headers: %s',
            $headerText,
            implode(', ', $headers)
        ));
    }

    /**
     * Checks if table header has such column name
     *
     * @param $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        $crawler = new Crawler($this->getHtml());

        /** @var \DOMElement $th */
        foreach ($crawler->filter('th') as $th) {
            if (strtolower(trim($th->textContent)) === strtolower($columnName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if table header has mass actions column of checkboxes
     *
     * @return bool
     */
    public function hasMassActionColumn()
    {
        return $this->has('css', 'th.grid-header-cell-massAction');
    }

    /**
     * Total columns number, NOT including action & mass action columns
     */
    public function getColumnsCount(): int
    {
        $crawler = new Crawler($this->getHtml());
        return $crawler->filter('th.grid-header-cell:not(.action-column):not(.grid-header-cell-massAction)')->count();
    }
}
