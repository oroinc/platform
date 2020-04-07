<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\Query\SqlWalker as BaseSqlWalker;

/**
 * Output SqlWalker decorator.
 */
class SqlWalker extends BaseSqlWalker
{
    use DecoratedSqlWalkerTrait;
}
