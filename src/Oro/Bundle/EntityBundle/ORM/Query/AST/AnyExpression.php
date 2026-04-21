<?php

namespace Oro\Bundle\EntityBundle\ORM\Query\AST;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Node;

/**
 * AST expression node that generates PostgreSQL `ANY()` construct for array comparison.
 * Used to compare a value with a set of values passed as a PostgreSQL array literal (e.g., '{1,2,3}').
 *
 * Unlike `IN()` which expands each value into a separate bind parameter, `ANY()` accepts a single
 * array parameter, avoiding the PostgreSQL limit of 65,535 bind parameters per prepared statement.
 */
class AnyExpression extends Node
{
    public function __construct(
        private readonly Node $input
    ) {
    }

    #[\Override]
    public function dispatch($walker): string
    {
        if (!$walker->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            throw new \RuntimeException(
                'The ANY() expression can only be used with PostgreSQL database.'
            );
        }

        return \sprintf('ANY(%s)', $this->input->dispatch($walker));
    }
}
