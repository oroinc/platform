<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ArrayToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    private function getTestTransformer(
        bool $allowNull = false,
        string $delimiter = ',',
        bool $filterUniqueValues = false
    ): ArrayToStringTransformer {
        return new ArrayToStringTransformer($delimiter, $filterUniqueValues, $allowNull);
    }

    public function testTransformForNullValue(): void
    {
        self::assertSame('', $this->getTestTransformer()->transform(null));
        self::assertSame('', $this->getTestTransformer(true)->transform(null));
    }

    public function testTransformForEmptyArrayValue(): void
    {
        self::assertSame('', $this->getTestTransformer()->transform([]));
        self::assertSame('', $this->getTestTransformer(true)->transform([]));
    }

    public function testTransformForNotArrayValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array.');

        $this->getTestTransformer()->transform(123);
    }

    public function testTransformForDefaultOptions(): void
    {
        self::assertSame('1,2,3', $this->getTestTransformer()->transform(['1', '2', '3']));
    }

    public function testTransformForDelimiterThatShouldBeTrimmed(): void
    {
        self::assertSame('1,2,3', $this->getTestTransformer(false, ' , ')->transform(['1', '2', '3']));
    }

    public function testTransformForSpaceDelimiter(): void
    {
        self::assertSame('1 2 3', $this->getTestTransformer(false, ' ')->transform(['1', '2', '3']));
    }

    public function testTransformForNotUniqueElementsInArrayAndWithoutFilterUniqueValuesOption(): void
    {
        self::assertSame(
            '1,1,2,2,3,3,4,4',
            $this->getTestTransformer()->transform(['1', '1', '2', '2', '3', '3', '4', '4'])
        );
    }

    public function testTransformForNotUniqueElementsInArrayAndWithFilterUniqueValuesOption(): void
    {
        self::assertSame(
            '1,2,3,4',
            $this->getTestTransformer(false, ',', true)->transform(['1', '1', '2', '2', '3', '3', '4', '4'])
        );
    }

    public function testReverseTransformForNullValue(): void
    {
        self::assertSame([], $this->getTestTransformer()->reverseTransform(null));
        self::assertNull($this->getTestTransformer(true)->reverseTransform(null));
    }

    public function testReverseTransformForEmptyStringValue(): void
    {
        self::assertSame([], $this->getTestTransformer()->reverseTransform(''));
        self::assertNull($this->getTestTransformer(true)->reverseTransform(''));
    }

    public function testReverseTransformForEmptyJsonArrayStringValue(): void
    {
        self::assertSame([], $this->getTestTransformer()->reverseTransform('[]'));
        self::assertNull($this->getTestTransformer(true)->reverseTransform('[]'));
    }

    public function testReverseTransformForNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->getTestTransformer()->reverseTransform(123);
    }

    public function testReverseTransformForDefaultOptions(): void
    {
        self::assertSame(['1', '2', '3'], $this->getTestTransformer()->reverseTransform('1,2,3'));
    }

    public function testReverseTransformForElementsThatShouldBeTrimmed(): void
    {
        self::assertSame(['1', '2', '3', '4'], $this->getTestTransformer()->reverseTransform(' , 1 , 2 , , 3 , 4,  '));
    }

    public function testReverseTransformForDelimiterThatShouldBeTrimmed(): void
    {
        self::assertSame(['1', '2', '3'], $this->getTestTransformer(false, ' , ')->reverseTransform('1,2,3'));
    }

    public function testReverseTransformForSpaceDelimiter(): void
    {
        self::assertSame(['1', '2', '3'], $this->getTestTransformer(false, ' ')->reverseTransform('1 2 3'));
    }

    public function testReverseTransformForNotUniqueElementsInArrayAndWithoutFilterUniqueValuesOption(): void
    {
        self::assertSame(
            ['1', '1', '2', '2', '3', '3', '4', '4'],
            $this->getTestTransformer()->reverseTransform('1,1,2,2,3,3,4,4')
        );
    }

    public function testReverseTransformForNotUniqueElementsInArrayAndWithFilterUniqueValuesOption(): void
    {
        self::assertSame(
            ['1', '2', '3', '4'],
            $this->getTestTransformer(false, ',', true)->reverseTransform('1,1,2,2,3,3,4,4')
        );
    }
}
