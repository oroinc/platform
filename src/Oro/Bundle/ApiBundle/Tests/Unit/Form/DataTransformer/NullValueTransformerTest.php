<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

class NullValueTransformerTest extends TestCase
{
    private DataTransformerInterface&MockObject $innerTransformer;
    private NullValueTransformer $nullValueTransformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerTransformer = $this->createMock(DataTransformerInterface::class);

        $this->nullValueTransformer = new NullValueTransformer($this->innerTransformer);
    }

    public function testInnerTransformerGetterAndSetter(): void
    {
        self::assertSame($this->innerTransformer, $this->nullValueTransformer->getInnerTransformer());

        $anotherInnerTransformer = $this->createMock(DataTransformerInterface::class);
        $this->nullValueTransformer->setInnerTransformer($anotherInnerTransformer);
        self::assertSame($anotherInnerTransformer, $this->nullValueTransformer->getInnerTransformer());
    }

    public function testTransformForNull(): void
    {
        $this->innerTransformer->expects(self::never())
            ->method('transform');

        self::assertNull($this->nullValueTransformer->transform(null));
    }

    public function testTransformForNotNull(): void
    {
        $value = 'test';
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->transform($value));
    }

    public function testReverseTransformForNull(): void
    {
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->reverseTransform(null));
    }

    public function testReverseTransformForEmptyString(): void
    {
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->reverseTransform(''));
    }

    public function testReverseTransformWhenInnerTransformerReturnsNullAndInputValueIsEmptyString(): void
    {
        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn(null);

        self::assertSame('', $this->nullValueTransformer->reverseTransform(''));
    }

    public function testReverseTransformWhenInnerTransformerReturnsNullButInputValueIsNotEmptyString(): void
    {
        $value = 'test';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn(null);

        self::assertNull($this->nullValueTransformer->reverseTransform($value));
    }

    public function testReverseTransformForNotNullAndNotEmptyString(): void
    {
        $value = 'test';
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->reverseTransform($value));
    }
}
