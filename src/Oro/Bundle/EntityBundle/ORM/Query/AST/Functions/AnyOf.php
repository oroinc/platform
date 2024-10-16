<?php

namespace Oro\Bundle\EntityBundle\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * The "ANYOF" DQL function.
 * Can be used to compare a value with a set of values returned by a subquery.
 * Differences between "ANYOF" function and Doctrine "ANY()" function expression:
 *  - no need to specify SELECT clause in a subquery.
 * Example of usage: ANYOF(e.field_name)
 */
class AnyOf extends FunctionNode
{
    private Node|string $entityFieldPath;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->entityFieldPath = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            throw new \RuntimeException(
                'The ANYOF DQL function should be used only with PostgreSQL database.'
            );
        }

        return 'ANY('
            . $this->entityFieldPath->dispatch($sqlWalker)
            . ')';
    }
}
