<?php

namespace Oro\Component\Layout\ExpressionLanguage;

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

    private array $expressions = [];

    public function __construct(ExpressionLanguage $expressionLanguage, Filesystem $fs, string $cacheFilePath)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->fs = $fs;
        $this->cacheFilePath = $cacheFilePath;
    }

    public function collect(string $expression): void
    {
        if (in_array($expression, $this->expressions, true)) {
            return;
        }
        $this->expressions[] = $expression;
    }

    public function write(): void
    {
        $content = "<?php return [\n";
        foreach ($this->expressions as $expression) {
            try {
                $parsedExpression = $this->expressionLanguage->parse($expression, ['data', 'context']);

                if ($this->shouldBeSkipped($parsedExpression->getNodes())) {
                    continue;
                }
                $compiled = $this->expressionLanguage->compile($parsedExpression, ['data', 'context']);
                $content .= '    \''.$expression.'\' => static function ($context, $data) { return '.$compiled."; },\n";
            } catch (SyntaxError $error) {
                // do not cache an expression when it has extra variables
            }
        }

        $content .= '];';

        $this->fs->dumpFile($this->cacheFilePath, $content);
    }

    /**
     * When an expression works only with the context variable, there is no need to cache it,
     * as it's evaluated during layout tree build.
     *
     * @param Node $node
     * @return bool
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
