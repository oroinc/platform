<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\AbstractMatcher;
use Oro\Component\ChainProcessor\ExpressionParser;

/**
 * This applicable checker allows to check whether a specific request type
 * is matched to a request type expression.
 */
class RequestExpressionMatcher extends AbstractMatcher
{
    /** @var array [expression => parsed expression, ...] */
    private array $expressions = [];

    public function matchValue(mixed $expression, RequestType $requestType): bool
    {
        if (!$expression) {
            return true;
        }

        if (isset($this->expressions[$expression])) {
            $expr = $this->expressions[$expression];
        } else {
            $expr = ExpressionParser::parse($expression);
            $this->expressions[$expression] = $expr;
        }

        return $this->isMatch($expr, $requestType, '');
    }
}
