<?php

namespace Oro\Bundle\EntityBundle\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * The "ARRAY_CONTAINS" DQL function for JSONB PostgreSQL columns.
 * "ARRAY_CONTAINS" "(" ArithmeticPrimary "," InputParameter ")"
 * Example of usage: ARRAY_CONTAINS(e.jsonb_field, :value_parameter)
 */
class ArrayContains extends FunctionNode
{
    private Node|string $entityFieldPath;
    private InputParameter $value;

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->entityFieldPath = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->value = $parser->InputParameter();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            throw new \RuntimeException(
                'The ARRAY_CONTAINS DQL function should be used only with PostgreSQL database.'
            );
        }

        return '('
            . $this->entityFieldPath->dispatch($sqlWalker)
            . ' @> ('
            . $sqlWalker->walkInputParameter($this->value)
            . ')::jsonb)';
    }
}
