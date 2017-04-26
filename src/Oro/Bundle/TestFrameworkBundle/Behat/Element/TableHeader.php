<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Symfony\Component\DomCrawler\Crawler;

class TableHeader extends Element
{
    /**
     * Try to guess header and return number of column starting from 1
     *
     * @param string $headerText Header of table column
     * @return int Number of column starting from 1
     */
    public function getColumnNumber($headerText)
    {
        $crawler = new Crawler($this->getHtml());

        $i = 1;

        /** @var \DOMElement $th */
        foreach ($crawler->filter('th')->siblings() as $th) {
            if (false !== stripos($th->textContent, $headerText)) {
                return $i;
            }

            $i++;
        }

        self::fail(sprintf('Can\'t find link with "%s" header', $headerText));
    }
}
