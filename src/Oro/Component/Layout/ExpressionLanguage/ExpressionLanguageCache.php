<?php

namespace Oro\Component\Layout\ExpressionLanguage;

use Psr\Log\LoggerInterface;

/**
 * Provides functionality to get compiled expressions stored in the PHP file cache.
 */
class ExpressionLanguageCache
{
    private string $cacheFilePath;
    private LoggerInterface $logger;
    private ?array $compiledExpressions = null;
    private ?array $compiledExpressionsWithExtraParams = null;

    public function __construct(string $cacheFilePath, LoggerInterface $logger)
    {
        $this->cacheFilePath = $cacheFilePath;
        $this->logger = $logger;
    }

    public function getClosure(string $expression): ?\Closure
    {
        if (null === $this->compiledExpressions) {
            $this->loadExpressions();
        }

        return $this->compiledExpressions[$expression] ?? null;
    }

    public function getClosureWithExtraParams(string $expression): ?ClosureWithExtraParams
    {
        if (null === $this->compiledExpressionsWithExtraParams) {
            $this->loadExpressions();
        }

        if (!isset($this->compiledExpressionsWithExtraParams[$expression])) {
            return null;
        }

        $result = $this->compiledExpressionsWithExtraParams[$expression];
        if ($result instanceof ClosureWithExtraParams) {
            return $result;
        }

        $result = new ClosureWithExtraParams($result[0], $result[1], $expression);
        $this->compiledExpressionsWithExtraParams[$expression] = $result;

        return $result;
    }

    private function loadExpressions(): void
    {
        if (file_exists($this->cacheFilePath)) {
            $data = include $this->cacheFilePath;
            $this->compiledExpressions = $data['closures'];
            $this->compiledExpressionsWithExtraParams = $data['closuresWithExtraParams'];
        } else {
            $this->compiledExpressions = [];
            $this->compiledExpressionsWithExtraParams = [];
            $this->logger->error(
                'The file with compiled layout expressions does not exist.',
                ['file' => $this->cacheFilePath]
            );
        }
    }
}
