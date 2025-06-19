<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\Select2ArrayToStringTransformerDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

class Select2ArrayToStringTransformerDecoratorTest extends TestCase
{
    private DataTransformerInterface&MockObject $transformer;
    private Select2ArrayToStringTransformerDecorator $transformerDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = $this->createMock(DataTransformerInterface::class);

        $this->transformerDecorator = new Select2ArrayToStringTransformerDecorator($this->transformer);
    }

    public function testTransform(): void
    {
        $value = ['some array value'];

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($value);

        $this->transformerDecorator->transform($value);
    }

    public function testReverseTransform(): void
    {
        $value = 'some string value';

        $this->transformer->expects($this->once())
            ->method('reverseTransform')
            ->with($value);

        $this->transformerDecorator->reverseTransform($value);
    }
}
