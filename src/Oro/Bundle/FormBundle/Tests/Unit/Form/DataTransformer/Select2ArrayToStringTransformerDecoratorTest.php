<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\Select2ArrayToStringTransformerDecorator;
use Symfony\Component\Form\DataTransformerInterface;

class Select2ArrayToStringTransformerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataTransformerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transformer;

    /**
     * @var Select2ArrayToStringTransformerDecorator
     */
    private $transformerDecorator;

    protected function setUp()
    {
        $this->transformer = $this->createMock(DataTransformerInterface::class);
        $this->transformerDecorator = new Select2ArrayToStringTransformerDecorator($this->transformer);
    }

    public function testTransform()
    {
        $value = ['some array value'];

        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with($value);

        $this->transformerDecorator->transform($value);
    }

    public function testReverseTransform()
    {
        $value = 'some string value';

        $this->transformer
            ->expects($this->once())
            ->method('reverseTransform')
            ->with($value);

        $this->transformerDecorator->reverseTransform($value);
    }
}
