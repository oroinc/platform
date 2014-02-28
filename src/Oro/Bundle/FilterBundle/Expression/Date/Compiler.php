<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

class Compiler
{
    /** @var Lexer */
    private $lexer;

    /** @var  Parser */
    private $parser;

    /**
     * @param string $string
     *
     * @return ExpressionResult
     */
    public function compile($string)
    {
        return $this->getParser()->parse($this->getLexer()->tokenize($string));
    }

    /**
     * @return Lexer
     */
    protected function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    /**
     * @return Parser
     */
    private function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser();
        }

        return $this->parser;
    }
}
