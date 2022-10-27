<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Handler\TypeCast\TypeCastingHandlerInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;

class TypeCastingHandlerRegistryTest extends \PHPUnit\Framework\TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    public function testGet(): void
    {
        $this->assertInstanceOf(
            TypeCastingHandlerInterface::class,
            $this->getTypeCastingHandlerRegistry()->get(Query::TYPE_TEXT)
        );
    }

    public function testGetWithInvalidType(): void
    {
        $exceptionMessage = 'No registered typecasting handlers that support the "invalid_or_not_exists_type" type.';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->getTypeCastingHandlerRegistry()->get('invalid_or_not_exists_type');
    }
}
