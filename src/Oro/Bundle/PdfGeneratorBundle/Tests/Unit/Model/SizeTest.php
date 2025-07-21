<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\Model\Unit;
use PHPUnit\Framework\TestCase;

final class SizeTest extends TestCase
{
    public function testConstructor(): void
    {
        $size = new Size(12.5, Unit::inches);

        self::assertSame(12.5, $size->getSize());
        self::assertSame(Unit::inches, $size->getUnit());
    }

    public function testCreateWithSizeInstance(): void
    {
        $originalSize = new Size(12.5, Unit::inches);
        $newSize = Size::create($originalSize);

        self::assertSame($originalSize, $newSize);
    }

    public function testCreateWithString(): void
    {
        $size = Size::create('12.5in');

        self::assertSame(12.5, $size->getSize());
        self::assertSame(Unit::inches, $size->getUnit());
    }

    public function testCreateWithFloat(): void
    {
        $size = Size::create(12.5);

        self::assertSame(12.5, $size->getSize());
        self::assertSame(Unit::inches, $size->getUnit());
    }

    public function testCreateWithInteger(): void
    {
        $size = Size::create(12);

        self::assertSame(12.0, $size->getSize());
        self::assertSame(Unit::inches, $size->getUnit());
    }

    public function testCreateWithInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unexpected value "invalid", expected format is "%f%s"');

        Size::create('invalid');
    }

    public function testCreateWithInvalidUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unexpected unit "invalid", available units are "in", "pt", "px", "mm", "cm", "pc"'
        );

        Size::create('12.5invalid');
    }

    public function testGetSize(): void
    {
        $size = new Size(12.5, Unit::inches);
        self::assertSame(12.5, $size->getSize());
    }

    public function testGetUnit(): void
    {
        $size = new Size(12.5, Unit::inches);
        self::assertSame(Unit::inches, $size->getUnit());
    }

    public function testToString(): void
    {
        $size = new Size(12.5, Unit::inches);
        self::assertSame('12.5in', (string)$size);
    }
}
