<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class SanitizeHTMLTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $htmlTagHelper;

    /**
     * @var SanitizeHTMLTransformer
     */
    private $transformer;

    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->transformer = new SanitizeHTMLTransformer($this->htmlTagHelper);
    }

    public function testTransform()
    {
        $value = '<p class="classname">sometext</p>';
        $expected = 'sometext';

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($value, 'default')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function testReverseTransform()
    {
        $value = '<p class="classname">sometext</p>';
        $expected = 'sometext';

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($value, 'default')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }
}
