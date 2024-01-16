<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

/**
 * Represents a value that should be decorated with DQL expression, e.g. LOWER(value).
 */
class ExpressionValue
{
    public function __construct(
        private readonly mixed $value,
        private readonly string $expressionTemplate
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function buildExpression(string $parameterPlaceholder): string
    {
        return sprintf($this->expressionTemplate, $parameterPlaceholder);
    }
}
