<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridHeader extends Element
{
    /**
     * @param string $headerText
     * @return int
     * @throws ExpectationException
     */
    public function getColumnNumber($headerText)
    {
        $header = $this->tryGuessHeaderLink($headerText);

        if (!$header) {
            throw new ExpectationException(
                sprintf('Can\'t find link with "%s" header', $headerText),
                $this->getDriver()
            );
        }

        $headerText = $header->getText();
        $headers = $this->getHeaders();

        for ($i = 1, $max = count($headers); $i <= $max; $i++) {
            if ($headers[$i]->getText() == $headerText) {
                return $i;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find link with "%s" header', $headerText),
            $this->getDriver()
        );
    }

    /**
     * @param string $header
     * @return Element
     * @throws ExpectationException
     */
    public function getHeaderLink($header)
    {
        if ($link = $this->tryGuessHeaderLink($header)) {
            return $link;
        }

        throw new ExpectationException(
            sprintf('Can\'t find link with "%s" header', $header),
            $this->getDriver()
        );
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
