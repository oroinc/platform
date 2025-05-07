<?php

namespace Oro\Bundle\SearchBundle\Test\Unit;

use Oro\Bundle\SearchBundle\Handler\TypeCast\DateTimeTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DecimalTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\IntegerTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TextTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TypeCastingHandlerRegistry;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Builds a typecasting registry with handlers for tests.
 */
trait SearchMappingTypeCastingHandlersTestTrait
{
    public function getTypeCastingHandlerRegistry(): TypeCastingHandlerRegistry
    {
        $handlers = new ServiceLocator([
            Query::TYPE_TEXT => function () {
                return new TextTypeCast();
            },
            Query::TYPE_INTEGER => function () {
                return new IntegerTypeCast();
            },
            Query::TYPE_DECIMAL => function () {
                return new DecimalTypeCast();
            },
            Query::TYPE_DATETIME => function () {
                return new DateTimeTypeCast();
            }
        ]);

        return new TypeCastingHandlerRegistry($handlers);
    }
}
