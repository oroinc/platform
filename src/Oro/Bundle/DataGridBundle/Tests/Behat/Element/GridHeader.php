<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridHeader extends Element
{
    /**
     * Try to guess header and return number of column starting from 1
     *
     * @param string $headerText Header of grid column
     * @return int Number of column starting from 1
     */
    public function getColumnNumber($headerText)
    {
        $header = $this->tryGuessHeaderLink($headerText);

        self::assertNotNull($header, sprintf('Can\'t find link with "%s" header', $headerText));

        $headerText = $header->getText();
        $headers = $this->getHeaders();

        for ($i = 1, $max = count($headers); $i <= $max; $i++) {
            if ($headers[$i]->getText() == $headerText) {
                return $i;
            }
        }

        self::fail(sprintf('Can\'t find link with "%s" header', $headerText));
    }

    /**
     * @param string $header
     * @return Element
     */
    public function getHeaderLink($header)
    {
        if ($link = $this->tryGuessHeaderLink($header)) {
            return $link;
        }

        self::fail(sprintf('Can\'t find link with "%s" header', $header));
    }

    /**
     * @param string $header
     * @return null|Element
     */
    public function tryGuessHeaderLink($header)
    {
        if ($link = $this->findHeaderLink($header)) {
            return $link;
        }

        if ($link = $this->findHeaderLink(ucwords(strtolower($header)))) {
            return $link;
        }

        if ($link = $this->findHeaderLink(ucfirst(strtolower($header)))) {
            return $link;
        }

        return null;
    }

    /**
     * @param string $header
     * @return Element|null
     */
    protected function findHeaderLink($header)
    {
        if ($link = $this->find('css', sprintf('a:contains("%s")', $header))) {
            return $link;
        }

        return null;
    }

    protected function getHeaders()
    {
        return $this->findAll('css', 'th.grid-cell');
    }
}
