<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\TypeNameConverter;
use Oro\Bundle\LayoutBundle\Layout\Serializer\TypeNameConverterInterface;

class TypeNameConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TypeNameConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter1;

    /** @var TypeNameConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter2;

    /** @var TypeNameConverter */
    private $typeNameConverter;

    protected function setUp(): void
    {
        $this->converter1 = $this->createMock(TypeNameConverterInterface::class);
        $this->converter2 = $this->createMock(TypeNameConverterInterface::class);

        $this->typeNameConverter = new TypeNameConverter([$this->converter1, $this->converter2]);
    }

    public function testGetShortTypeNameWhenFirstConverterReturnsIt(): void
    {
        $type = \stdClass::class;
        $shortType = 's';

        $this->converter1->expects(self::once())
            ->method('getShortTypeName')
            ->with($type)
            ->willReturn($shortType);
        $this->converter2->expects(self::never())
            ->method('getShortTypeName');

        self::assertEquals($shortType, $this->typeNameConverter->getShortTypeName($type));
        // check memory cache
        self::assertEquals($shortType, $this->typeNameConverter->getShortTypeName($type));
    }

    public function testGetShortTypeNameWhenSecondConverterReturnsIt(): void
    {
        $type = \stdClass::class;
        $shortType = 's';

        $this->converter1->expects(self::once())
            ->method('getShortTypeName')
            ->with($type)
            ->willReturn(null);
        $this->converter2->expects(self::once())
            ->method('getShortTypeName')
            ->with($type)
            ->willReturn($shortType);

        self::assertEquals($shortType, $this->typeNameConverter->getShortTypeName($type));
        // check memory cache
        self::assertEquals($shortType, $this->typeNameConverter->getShortTypeName($type));
    }

    public function testGetShortTypeNameWhenNoConvertersKnownAboutIt(): void
    {
        $type = \stdClass::class;

        $this->converter1->expects(self::once())
            ->method('getShortTypeName')
            ->with($type)
            ->willReturn(null);
        $this->converter2->expects(self::once())
            ->method('getShortTypeName')
            ->with($type)
            ->willReturn(null);

        self::assertNull($this->typeNameConverter->getShortTypeName($type));
        // check memory cache
        self::assertNull($this->typeNameConverter->getShortTypeName($type));
    }

    public function testGetTypeNameWhenFirstConverterReturnsIt(): void
    {
        $type = \stdClass::class;
        $shortType = 's';

        $this->converter1->expects(self::once())
            ->method('getTypeName')
            ->with($shortType)
            ->willReturn($type);
        $this->converter2->expects(self::never())
            ->method('getTypeName');

        self::assertEquals($type, $this->typeNameConverter->getTypeName($shortType));
        // check memory cache
        self::assertEquals($type, $this->typeNameConverter->getTypeName($shortType));
    }

    public function testGetTypeNameWhenSecondConverterReturnsIt(): void
    {
        $type = \stdClass::class;
        $shortType = 's';

        $this->converter1->expects(self::once())
            ->method('getTypeName')
            ->with($shortType)
            ->willReturn(null);
        $this->converter2->expects(self::once())
            ->method('getTypeName')
            ->with($shortType)
            ->willReturn($type);

        self::assertEquals($type, $this->typeNameConverter->getTypeName($shortType));
        // check memory cache
        self::assertEquals($type, $this->typeNameConverter->getTypeName($shortType));
    }

    public function testGetTypeNameWhenNoConvertersKnownAboutIt(): void
    {
        $shortType = 's';

        $this->converter1->expects(self::once())
            ->method('getTypeName')
            ->with($shortType)
            ->willReturn(null);
        $this->converter2->expects(self::once())
            ->method('getTypeName')
            ->with($shortType)
            ->willReturn(null);

        self::assertNull($this->typeNameConverter->getTypeName($shortType));
        // check memory cache
        self::assertNull($this->typeNameConverter->getTypeName($shortType));
    }
}
