<?php

namespace Oro\Bundle\EntityBundle\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * The "ISOYEAR" DQL function.
 * Returns the ISO 8601 week-numbering year that the date falls in.
 * Example of usage: ISOYEAR(e.field_name)
 */
class IsoYear extends FunctionNode
{
    private Node $entityFieldPath;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->entityFieldPath = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            throw new \RuntimeException(
                'The ISOYEAR DQL function should be used only with PostgreSQL database.'
            );
        }

        return 'EXTRACT(ISOYEAR FROM ' . $this->entityFieldPath->dispatch($sqlWalker) . ')';
    }
}
