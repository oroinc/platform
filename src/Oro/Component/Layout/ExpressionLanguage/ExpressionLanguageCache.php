<?php

namespace Oro\Component\Layout\ExpressionLanguage;

/**
 * Reads expressions from the PHP file cache
 */
class ExpressionLanguageCache
{
    private array $compiledExpressions = [];

    public function __construct(string $cacheFilePath)
    {
        $this->cacheFilePath = $cacheFilePath;

        if (file_exists($cacheFilePath)) {
            $this->compiledExpressions = include $cacheFilePath;
        }
    }

    public function getClosure($expression): ?\Closure
    {
        return $this->compiledExpressions[$expression] ?? null;
    }
}
