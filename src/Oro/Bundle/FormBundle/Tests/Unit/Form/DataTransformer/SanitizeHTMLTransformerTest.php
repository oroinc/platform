<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SanitizeHTMLTransformerTest extends TestCase
{
    private HtmlTagHelper&MockObject $htmlTagHelper;
    private SanitizeHTMLTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->transformer = new SanitizeHTMLTransformer($this->htmlTagHelper);
    }

    public function testTransform(): void
    {
        $value = '<p class="classname">sometext</p>';
        $expected = 'sometext';

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($value, 'default')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function testReverseTransform(): void
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
