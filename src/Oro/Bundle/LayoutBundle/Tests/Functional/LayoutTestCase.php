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
        $this->assertEquals($this->normalizeHtml($expected), $this->normalizeHtml($actual));
    }

    /**
     * Normalizes HTML string
     *
     * @param string $html
     *
     * @return string
     */
    protected function normalizeHtml($html)
    {
        // add line break after tag and its content
        $html = preg_replace('/\>\s*(\w)/', ">\n\${1}", $html);
        // add line break before closing tags
        $html = preg_replace('/\s+\<\//', "\n</", $html);
        // add line break between tags
        $html = preg_replace('/\>\s*\</', ">\n<", $html);

        // remove redundant spaces inside tag
        // for example
        // <label  for="element"   class="test"
        //        title="some title" >
        // will be converted to
        // <label for="element" class="test" title="some title">
        $html = preg_replace('/\"\s+([\w\-]+=)/', '" ${1}', $html);
        $html = preg_replace('/(\<\w+)\s+/', '${1} ', $html);
        $html = preg_replace('/\s+([\/]*\>)/', '${1}', $html);

        // remove spaces at the begin of a line
        $html = preg_replace('/^\s+/m', '', $html);

        // replace uid from id attributes, see AdditionalAttrExtension
        $html = preg_replace('/="(\w+)-uid-[a-z0-9]+"/', '="${1}"', $html);

        $html = rtrim($html);

        // remove asset version from HTML attributes
        $html = preg_replace('/\?version=.*?"/', '"', $html);

        return $html;
    }
}
