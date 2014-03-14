<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

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
     *
     * @return ExpressionResult
     */
    public function compile($string)
    {
        return $this->parser->parse($this->lexer->tokenize($string));
    }
}
