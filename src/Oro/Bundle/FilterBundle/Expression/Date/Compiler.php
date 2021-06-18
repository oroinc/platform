<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

/**
 * Responsible for compiling the datetime.
 */
class Compiler
{
    /** @var Lexer */
    private $lexer;

    /** @var Parser */
    private $parser;

    public function __construct(Lexer $lexer, Parser $parser)
    {
        $this->lexer  = $lexer;
        $this->parser = $parser;
    }

    /**
     * @param string $string
     * @param bool $returnRawToken
     *
     * @return ExpressionResult
     */
    public function compile(string $string, $returnRawToken = false)
    {
        return $this->parser->parse($this->lexer->tokenize($string), $returnRawToken);
    }
}
