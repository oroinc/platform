<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

class SanitizeHTMLTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $value
     * @param string $allowableTags
     * @param string $expected
     *
     * @dataProvider dataProvider
     */
    public function testTransform($value, $allowableTags, $expected)
    {
        $transformer = new SanitizeHTMLTransformer($allowableTags);

        $this->assertEquals(
            $expected,
            $transformer->transform($value)
        );

        $this->assertEquals(
            $expected,
            $transformer->reverseTransform($value)
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return array_merge($this->transformDataProvider(), $this->xssDataProvider());
    }

    /**
     * @link https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     *
     * @return array
     */
    protected function xssDataProvider()
    {
        $str = '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&' .
            '#x58&#x53&#x53&#x27&#x29>';

        return [
            'image' => ['<IMG SRC="javascript:alert(\'XSS\');">', null, ''],
            'script' => ['<script>alert(\'xss\');</script>', null, ''],
            'coded' => [$str, null, ''],
            'css expr' => ['<IMG STYLE="xss:expression(alert(\'XSS\'))">', null, '']
        ];
    }

    /**
     * @return array
     */
    protected function transformDataProvider()
    {
        return [
            'default' => ['sometext', null, 'sometext'],
            'not allowed tag' => ['<p>sometext</p>', 'a', 'sometext'],
            'allowed tag' => ['<p>sometext</p>', 'p', '<p>sometext</p>'],
            'mixed' => ['<p>sometext</p></br>', 'p', '<p>sometext</p>'],
            'attribute' => ['<p class="class">sometext</p>', 'p[class]', '<p class="class">sometext</p>'],
            'mixed attribute' => [
                '<p class="class">sometext</p><span data-attr="mixed">',
                'p[class]',
                '<p class="class">sometext</p>'
            ],
            'prepare allowed' => ['<a>first text</a><c>second text</c>', 'a, b', '<a>first text</a>second text'],
            'prepare not allowed' => ['<p>sometext</p>', 'a[class]', 'sometext'],
            'prepare with allowed' => ['<p>sometext</p>', 'a, p[class]', '<p>sometext</p>'],
            'prepare attribute' => ['<p>sometext</p>', 'a[class], p', '<p>sometext</p>'],
            'prepare attributes' => ['<p>sometext</p>', 'p[class|style]', '<p>sometext</p>'],
            'prepare or condition' => ['<p>sometext</p>', 'a[href|target=_blank], b/p', '<p>sometext</p>'],
            'prepare empty' => ['<p>sometext</p>', '[href|target=_blank],/', 'sometext'],
            'default attributes set' => ['<p>sometext</p>', '@[style],a', 'sometext'],
            'default attributes set with allowed' => ['<p>sometext</p>', '@[style],p', '<p>sometext</p>']
        ];
    }
}
