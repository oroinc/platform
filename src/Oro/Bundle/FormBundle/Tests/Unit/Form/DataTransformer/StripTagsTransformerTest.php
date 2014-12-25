<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\StripTagsTransformer;

class StripTagsTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $value
     * @param string $allowableTags
     * @param string $expected
     *
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $allowableTags, $expected)
    {
        $transformer = new StripTagsTransformer($allowableTags);

        $this->assertEquals(
            $expected,
            $transformer->transform($value)
        );
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'default' => ['sometext', null, 'sometext'],
            'not allowed tag' => ['<p>sometext</p>', null, 'sometext'],
            'allowed tag' => ['<p>sometext</p>', 'p', '<p>sometext</p>'],
            'mixed' => ['<p>sometext</p></br>', 'p', '<p>sometext</p>'],
            'attribute' => ['<p class="class">sometext</p>', 'p', '<p class="class">sometext</p>'],
            'mixed attribute' => [
                '<p class="class">sometext</p><span data-attr="mixed">',
                'p',
                '<p class="class">sometext</p>'
            ],
            'prepare allowed' => ['<a>first text</a><c>second text</c>', 'a, b', '<a>first text</a>second text'],
            'prepare not allowed' => ['<p>sometext</p>', 'a[class]', 'sometext'],
            'prepare with allowed' => ['<p>sometext</p>', 'a, p[class]', '<p>sometext</p>'],
            'prepare attribute' => ['<p>sometext</p>', 'a[class], p', '<p>sometext</p>'],
            'prepare attributes' => ['<p>sometext</p>', 'p[href|target=_blank]', '<p>sometext</p>'],
            'prepare or condition' => ['<p>sometext</p>', 'a[href|target=_blank], b/p', '<p>sometext</p>'],
            'prepare empty' => ['<p>sometext</p>', '[href|target=_blank],/', 'sometext'],
            'default attributes set' => ['<p>sometext</p>', '@[style]', 'sometext'],
            'default attributes set with allowed' => ['<p>sometext</p>', '@[style],p', '<p>sometext</p>']
        ];
    }

    public function testReverseTransform()
    {
        $transformer = new StripTagsTransformer();

        $this->assertEquals('value', $transformer->reverseTransform('value'));
    }
}
