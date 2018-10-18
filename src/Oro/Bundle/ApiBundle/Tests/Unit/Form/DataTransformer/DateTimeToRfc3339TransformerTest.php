<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToRfc3339Transformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer as BaseTransformer;

class DateTimeToRfc3339TransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|BaseTransformer */
    private $innerTransformer;

    /** @var DateTimeToRfc3339Transformer */
    private $transformer;

    protected function setUp()
    {
        $this->innerTransformer = $this->createMock(BaseTransformer::class);

        $this->transformer = new DateTimeToRfc3339Transformer($this->innerTransformer);
    }

    public function testTransform()
    {
        $value = new \DateTime();
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->transformer->transform($value));
    }

    public function testReverseTransformForStringRepresentsDateOnlyValue()
    {
        $value = '2017-01-21';
        $correctedValue = '2017-01-21T00:00:00Z';
        $transformedValue = new \DateTime($correctedValue);

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($correctedValue)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformForStringRepresentsDateTimeValue()
    {
        $value = '2017-01-21T10:20:30Z';
        $transformedValue = new \DateTime($value);

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformForEmptyStringValue()
    {
        $value = '';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn(null);

        self::assertNull($this->transformer->reverseTransform($value));
    }
}
