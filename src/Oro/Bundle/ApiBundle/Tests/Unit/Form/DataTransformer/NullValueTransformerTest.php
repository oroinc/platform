<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class NullValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $innerTransformer;

    /** @var NullValueTransformer */
    private $nullValueTransformer;

    protected function setUp()
    {
        $this->innerTransformer = $this->createMock(DataTransformerInterface::class);

        $this->nullValueTransformer = new NullValueTransformer($this->innerTransformer);
    }

    public function testInnerTransformerGetterAndSetter()
    {
        self::assertSame($this->innerTransformer, $this->nullValueTransformer->getInnerTransformer());

        $anotherInnerTransformer = $this->createMock(DataTransformerInterface::class);
        $this->nullValueTransformer->setInnerTransformer($anotherInnerTransformer);
        self::assertSame($anotherInnerTransformer, $this->nullValueTransformer->getInnerTransformer());
    }

    public function testTransformForNull()
    {
        $this->innerTransformer->expects(self::never())
            ->method('transform');

        self::assertNull($this->nullValueTransformer->transform(null));
    }

    public function testTransformForNotNull()
    {
        $value = 'test';
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->transform($value));
    }

    public function testReverseTransformForNull()
    {
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->reverseTransform(null));
    }

    public function testReverseTransformForEmptyString()
    {
        $transformedValue = 'transformed';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn($transformedValue);

        self::assertEquals($transformedValue, $this->nullValueTransformer->reverseTransform(''));
    }

    public function testReverseTransformWhenInnerTransformerReturnsNullAndInputValueIsEmptyString()
    {
        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with(self::identicalTo(''))
            ->willReturn(null);

        self::assertSame('', $this->nullValueTransformer->reverseTransform(''));
    }

    public function testReverseTransformWhenInnerTransformerReturnsNullButInputValueIsNotEmptyString()
    {
        $value = 'test';

        $this->innerTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn(null);

        self::assertNull($this->nullValueTransformer->reverseTransform($value));
    }

    public function testReverseTransformForNotNullAndNotEmptyString()
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
