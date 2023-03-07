<?php

namespace Oro\Bundle\SearchBundle\Test\Unit;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\TraversableArrayObject;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DateTimeTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DecimalTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\IntegerTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TextTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TypeCastingHandlerRegistry;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Builds a typecasting registry with handlers for tests.
 */
trait SearchMappingTypeCastingHandlersTestTrait
{
    public function getTypeCastingHandlerRegistry(): TypeCastingHandlerRegistry
    {
        $handlers = new TraversableArrayObject([
            Query::TYPE_TEXT => new TextTypeCast(),
            Query::TYPE_INTEGER => new IntegerTypeCast(),
            Query::TYPE_DECIMAL => new DecimalTypeCast(),
            Query::TYPE_DATETIME => new DateTimeTypeCast()
        ]);

        return new TypeCastingHandlerRegistry($handlers);
    }
}
