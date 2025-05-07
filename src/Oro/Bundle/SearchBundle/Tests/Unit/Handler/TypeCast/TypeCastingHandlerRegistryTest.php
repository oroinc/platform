<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Handler\TypeCast\TypeCastingHandlerInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;
use PHPUnit\Framework\TestCase;

class TypeCastingHandlerRegistryTest extends TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    public function testGet(): void
    {
        self::assertInstanceOf(
            TypeCastingHandlerInterface::class,
            $this->getTypeCastingHandlerRegistry()->get(Query::TYPE_TEXT)
        );
    }

    public function testGetWithInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'No registered typecasting handlers that support the "invalid_or_not_exists_type" type.'
        );
        $this->getTypeCastingHandlerRegistry()->get('invalid_or_not_exists_type');
    }
}
