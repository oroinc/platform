<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

class SanitizeHTMLTransformerTest extends \PHPUnit_Framework_TestCase
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
    public function transformDataProvider()
    {
        return [];
    }
}
