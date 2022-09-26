<?php

namespace Oro\Bundle\EntityBundle\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * The "JSON_AGG" DQL function.
 * "JSON_AGG" "(" InputParameter "ORDER" "BY" OrderByItem {"," OrderByItem}* ")"
 * Examples of usage: JSON_AGG(e.string_field), JSON_AGG(CAST(1 as string) ORDER BY e.string_field DESC)
 */
class JsonAgg extends FunctionNode
{
    private const PARAMETER_KEY = 'expression';
    private const ORDER_KEY = 'order';

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();

        $this->parameters[self::PARAMETER_KEY] = $parser->StringPrimary();

        if ($lexer->isNextToken(Lexer::T_ORDER)) {
            $this->parameters[self::ORDER_KEY] = $parser->OrderByClause();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $result = 'json_agg(';

        /** @var Node $pathExpression */
        $pathExpression = $this->parameters[self::PARAMETER_KEY];
        $field = $pathExpression->dispatch($sqlWalker);

        $result .= $field;

        if (!empty($this->parameters[self::ORDER_KEY])) {
            $result .= $sqlWalker->walkOrderByClause($this->parameters[self::ORDER_KEY]);
        }

        $result .= ')';

        return $result;
    }
}
