<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

//use Oro\Bundle\FilterBundle\Provider\DatevariablesInterface;

class Compiler
{
//    /** @var array */
//    protected $allowedExpressionsWithTokens = [
//        DatevariablesInterface::VAR_THIS_DAY,
//        DatevariablesInterface::VAR_THIS_WEEK,
//        DatevariablesInterface::VAR_THIS_MONTH,
//        DatevariablesInterface::VAR_THIS_QUARTER,
//        DatevariablesInterface::VAR_THIS_YEAR,
//        DatevariablesInterface::VAR_FDQ,
//        DatevariablesInterface::VAR_FMQ
//    ];


    /** @var Lexer */
    private $lexer;

    /** @var  Parser */
    private $parser;

    /**
     * @param string $string
     *
     * @return \Carbon\Carbon TODO fix
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
