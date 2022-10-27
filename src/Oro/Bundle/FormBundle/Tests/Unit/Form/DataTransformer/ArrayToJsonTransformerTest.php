<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ArrayToJsonTransformerTest extends \PHPUnit\Framework\TestCase
{
    private function getTestTransformer(bool $allowNull = false): ArrayToJsonTransformer
    {
        return new ArrayToJsonTransformer($allowNull);
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

    public function testTransformForNotEmptyArray(): void
    {
        self::assertSame('[1,2]', $this->getTestTransformer()->transform([1, 2]));
    }

    public function testTransformForNotEmptyAssociativeArray(): void
    {
        self::assertSame('{"key":"value"}', $this->getTestTransformer()->transform(['key' => 'value']));
    }

    public function testTransformForNotArrayValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array.');

        $this->getTestTransformer()->transform(123);
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

    public function testReverseTransformForEmptyJsonObjectStringValue(): void
    {
        self::assertSame([], $this->getTestTransformer()->reverseTransform('{}'));
        self::assertNull($this->getTestTransformer(true)->reverseTransform('{}'));
    }

    public function testReverseTransformForNotEmptyJsonArrayStringValue(): void
    {
        self::assertSame([1, 2], $this->getTestTransformer()->reverseTransform('[1,2]'));
    }

    public function testReverseTransformForNotEmptyJsonObjectStringValue(): void
    {
        self::assertSame(['key' => 'value'], $this->getTestTransformer()->reverseTransform('{"key":"value"}'));
    }

    public function testReverseTransformForNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->getTestTransformer()->reverseTransform(123);
    }

    public function testReverseTransformForNotInvalidJsonStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The malformed JSON.');

        $this->getTestTransformer()->reverseTransform('{"key":}');
    }
}
