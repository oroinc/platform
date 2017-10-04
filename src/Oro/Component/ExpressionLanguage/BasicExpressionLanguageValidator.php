<?php

namespace Oro\Component\ExpressionLanguage;

use Oro\Component\Expression\ExpressionParser;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class BasicExpressionLanguageValidator
{
    /**
     * @var ExpressionParser
     */
    private $expressionParser;

    /**
     * @param ExpressionLanguage $expressionParser
     */
    public function __construct(ExpressionLanguage $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * @param string $expression
     *
     * @return bool|string
     */
    public function validate(string $expression)
    {
        try {
            $this->expressionParser->parse($expression, []);
        } catch (SyntaxError $ex) {
            return $ex->getMessage();
        }
        return false;
    }
}
