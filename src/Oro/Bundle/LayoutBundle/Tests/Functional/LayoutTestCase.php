<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LayoutTestCase extends WebTestCase
{
    /**
     * Asserts that two variables contains HTML are equal
     *
     * @param string $expected
     * @param string $actual
     */
    protected function assertHtmlEquals($expected, $actual)
    {
        $this->assertEquals($this->prepareHtml($expected), $this->prepareHtml($actual));
    }

    /**
     * Removes not important whitespaces from the given HTML string
     *
     * @param string $html
     *
     * @return string
     */
    protected function prepareHtml($html)
    {
        $html = preg_replace('/\\n\s*\\n/', "\n", $html);
        $html = preg_replace('/\\n\s+\</', "\n<", $html);
        $html = rtrim($html);

        return $html;
    }
}
