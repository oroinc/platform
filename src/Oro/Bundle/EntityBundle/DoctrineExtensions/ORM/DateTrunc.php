<?php

namespace Oro\Bundle\EntityBundle\DoctrineExtensions\ORM;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Implementation of DATE_TRUNC SQL function.
 */
class DateTrunc extends FunctionNode
{
    private ?Node $fieldText = null;
    private ?Node $fieldTimestamp = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->fieldText = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->fieldTimestamp = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            'DATE_TRUNC(%s, %s)',
            $this->fieldText->dispatch($sqlWalker),
            $this->fieldTimestamp->dispatch($sqlWalker)
        );
    }
}
