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
     * @return ExpressionResult
     */
    public function compile($string, $returnRawToken = false)
    {
        return $this->parser->parse($this->lexer->tokenize($string), $returnRawToken);
    }

    /**
     * @param string $date
     * @param bool $returnRawToken
     * @param string|null $timeZone
     *
     * @return int|mixed|ExpressionResult|string
     */
    public function compileWithTimeZone(string $date, bool $returnRawToken = false, ?string $timeZone = null)
    {
        return $this->parser->parseWithTimeZone($this->lexer->tokenize($date), $returnRawToken, $timeZone);
    }
}
