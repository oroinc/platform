<?php

namespace Oro\Component\Layout\ExpressionLanguage;

/**
 * Represents compiled expression that has extra parameters.
 */
final class ClosureWithExtraParams
{
    private \Closure $closure;
    private array $extraParamNames;
    private string $expression;

    public function __construct(\Closure $closure, array $extraParamNames, string $expression)
    {
        $this->closure = $closure;
        $this->extraParamNames = $extraParamNames;
        $this->expression = $expression;
    }

    public function getClosure(): \Closure
    {
        return $this->closure;
    }

    /**
     * @return string[]
     */
    public function getExtraParamNames(): array
    {
        return $this->extraParamNames;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}
