<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ImportExportBundle\Form\DataTransformer\DateTimeToImportExportStringTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DateTimeToImportExportStringTransformerTest extends TestCase
{
    private AbstractNormalizer&MockObject $normalizer;
    private DateTimeToImportExportStringTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer = $this->createMock(AbstractNormalizer::class);
        $this->transformer = new DateTimeToImportExportStringTransformer($this->normalizer);
    }

    public function testTransformWithNullValue(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformWithInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a \DateTimeInterface.');

        $this->transformer->transform('invalid');
    }

    public function testTransformWithValidDateTime(): void
    {
        $dateTime = new \DateTimeImmutable('2024-01-01 00:00:00');
        $expected = '2024-01-01T00:00:00+00:00';

        $this->normalizer->expects(self::once())
            ->method('normalize')
            ->with($dateTime, null, ['type' => 'datetime'])
            ->willReturn($expected);

        self::assertSame($expected, $this->transformer->transform($dateTime));
    }

    public function testReverseTransformWithNullValue(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformWithInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->transformer->reverseTransform(12345);
    }

    public function testReverseTransformWithValidString(): void
    {
        $dateTimeString = '2024-01-01T00:00:00+00:00';
        $expectedDateTime = new \DateTimeImmutable('2024-01-01 00:00:00');

        $this->normalizer->expects(self::once())
            ->method('denormalize')
            ->with($dateTimeString, 'DateTime', null, ['type' => 'datetime'])
            ->willReturn($expectedDateTime);

        self::assertSame($expectedDateTime, $this->transformer->reverseTransform($dateTimeString));
    }

    public function testReverseTransformThrowsExceptionOnFailure(): void
    {
        $this->normalizer->expects(self::once())
            ->method('denormalize')
            ->willThrowException(new \Exception('Failed to denormalize.'));

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Failed to denormalize.');

        $this->transformer->reverseTransform('invalid');
    }
}
