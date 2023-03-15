<?php
/**
 * Declare custom SQL functions available in the Mock platform
 */
namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Oro\ORM\Query\AST\Platform\Functions\Mysql\Cast as BaseCast;

/**
 * The namespace of custom functions is hardcoded in \Oro\ORM\Query\AST\FunctionFactory::create(),
 * so to be able to use it, you should create make it available under a different name(space):
 * <code>
 *  if (!\class_exists('Oro\ORM\Query\AST\Platform\Functions\Mock\Cast', false)) {
 *      \class_alias(
 *          \Oro\Component\Testing\Unit\ORM\Mocks\Cast::class,
 *          'Oro\ORM\Query\AST\Platform\Functions\Mock\Cast'
 *      );
 *  }
 * <code>
 * @see \Oro\Component\Testing\Unit\ORM\OrmTestCase::getTestEntityManager() - usage example
 * @see \Oro\ORM\Query\AST\FunctionFactory::create()
 */
class Cast extends BaseCast
{
}
