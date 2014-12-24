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
        ];
    }

    public function testReverseTransform()
    {
        $transformer = new StripTagsTransformer();

        $this->assertEquals('value', $transformer->reverseTransform('value'));
    }

    /**
     * @param string $allowableTags
     * @param string $expected
     *
     * @dataProvider stripDataProvider
     */
    public function testPrepareAllowedTagsList($allowableTags, $expected)
    {
        $transformer = new StripTagsTransformer();

        $this->assertEquals(
            $expected,
            $transformer->prepareAllowedTagsList($allowableTags)
        );
    }

    /**
     * @return array
     */
    public function stripDataProvider()
    {
        return [
            'default' => ['a, b', '<a><b>'],
            'attribute' => ['a[class], b', '<a><b>'],
            'attributes' => ['a[href|target=_blank], b', '<a><b>'],
            'tag or tag' => ['a[href|target=_blank], b/p', '<a><b><p>'],
        ];
    }
}
