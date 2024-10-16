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
 * The "STRING_TO_ARRAY" DQL function for TEXT PostgreSQL columns.
 * Example of usage: STRING_TO_ARRAY(e.field_name, :delimiter_parameter)
 */
class StringToArray extends FunctionNode
{
    private Node|string $entityFieldPath;
    private InputParameter $delimiter;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->entityFieldPath = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->delimiter = $parser->InputParameter();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            throw new \RuntimeException(
                'The STRING_TO_ARRAY DQL function should be used only with PostgreSQL database.'
            );
        }

        return 'STRING_TO_ARRAY('
            . $this->entityFieldPath->dispatch($sqlWalker)
            . ','
            . $sqlWalker->walkInputParameter($this->delimiter)
            . ')';
    }
}
