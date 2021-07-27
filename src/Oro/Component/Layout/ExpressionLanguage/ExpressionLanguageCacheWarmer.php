<?php

namespace Oro\Component\Layout\ExpressionLanguage;

use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Collects and writes expressions to the PHP file cache
 */
class ExpressionLanguageCacheWarmer
{
    private ExpressionLanguage $expressionLanguage;
    private Filesystem $fs;
    private string $cacheFilePath;
    private LoggerInterface $logger;
    private array $expressions = [];

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        Filesystem $fs,
        string $cacheFilePath,
        LoggerInterface $logger
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->fs = $fs;
        $this->cacheFilePath = $cacheFilePath;
        $this->logger = $logger;
    }

    public function collect(string $expression): void
    {
        if (!isset($this->expressions[$expression])) {
            $this->expressions[$expression] = $expression;
        }
    }

    public function write(): void
    {
        $closures = '';
        $closuresWithExtraParams = '';
        foreach ($this->expressions as $expression) {
            $this->logger->debug('Compile the layout expression.', ['expression' => $expression]);
            try {
                $compiled = $this->compile($expression);
                if ($compiled) {
                    $closures .= $compiled;
                }
            } catch (SyntaxError $e) {
                $extraParamName = $this->checkForExtraVariable($e);
                if ($extraParamName) {
                    $extraParamNames = [$extraParamName];
                    $compiled = $this->tryToCompileWithExtraParams($expression, $extraParamNames);
                    if ($compiled) {
                        $closuresWithExtraParams .= $compiled;
                        continue;
                    }
                }
                $this->logger->error(
                    'The layout expression cannot be cached because it cannot be compiled.',
                    ['expression' => $expression, 'message' => $e->getMessage()]
                );
            }
        }

        $content = "<?php return [\n'closures' => [\n"
            . $closures
            . "],\n'closuresWithExtraParams' => [\n"
            . $closuresWithExtraParams
            . "]\n];";

        $this->fs->dumpFile($this->cacheFilePath, $content);
    }

    /**
     * @throws SyntaxError
     */
    private function compile(string $expression): ?string
    {
        $closureParamNames = ['data', 'context'];
        $parsedExpression = $this->expressionLanguage->parse($expression, $closureParamNames);
        if ($this->shouldBeSkipped($parsedExpression->getNodes())) {
            $this->logSkipped($expression);
            return null;
        }

        $compiled = $this->expressionLanguage->compile($parsedExpression, $closureParamNames);

        return  '    \''
            . $expression
            . '\' => static function ($context, $data) { return '
            . $compiled
            . "; },\n";
    }

    private function tryToCompileWithExtraParams(string $expression, array &$extraParamNames): ?string
    {
        try {
            return $this->compileWithExtraParams($expression, $extraParamNames);
        } catch (SyntaxError $e) {
            $extraParamName = $this->checkForExtraVariable($e);
            if ($extraParamName && !\in_array($extraParamName, $extraParamNames, true)) {
                $extraParamNames[] = $extraParamName;

                return $this->tryToCompileWithExtraParams($expression, $extraParamNames);
            }

            return null;
        }
    }

    /**
     * @throws SyntaxError
     */
    private function compileWithExtraParams(string $expression, array $extraParamNames): ?string
    {
        $closureParamNames = array_merge(['data', 'context'], $extraParamNames);
        $parsedExpression = $this->expressionLanguage->parse($expression, $closureParamNames);
        if ($this->shouldBeSkipped($parsedExpression->getNodes())) {
            $this->logSkipped($expression);
            return null;
        }

        $compiled = $this->expressionLanguage->compile($parsedExpression, $closureParamNames);

        return  '    \''
            . $expression
            . '\' => [static function ($context, $data, $'
            . implode(', $', $extraParamNames)
            . ') { return '
            . $compiled
            . "; }, ['"
            . implode("', '", $extraParamNames)
            . "']],\n";
    }

    private function checkForExtraVariable(SyntaxError $e): ?string
    {
        if (preg_match('/Variable "(\w+)" is not valid/', $e->getMessage(), $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function logSkipped(string $expression): void
    {
        $this->logger->info(
            'There is no need to cache the layout expression.',
            ['expression' => $expression]
        );
    }

    /**
     * When an expression works only with the context variable, there is no need to cache it,
     * as it's evaluated during layout tree build.
     */
    private function shouldBeSkipped(Node $node): bool
    {
        if ($node instanceof NameNode && $node->attributes['name'] !== 'context') {
            return false;
        }

        foreach ($node->nodes as $childNode) {
            if (!$this->shouldBeSkipped($childNode)) {
                return false;
            }
        }

        return true;
    }
}
