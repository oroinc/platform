<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Provider\ExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ExpressionLanguage;

/**
 * Filter out import directives where import_condition is present and not true.
 */
class ImportConditionFilter implements ImportFilterInterface
{
    private ?ExpressionLanguage $expressionLanguage = null;

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    #[\Override]
    public function filter(array $imports): array
    {
        return array_filter($imports, $this->filterImportDirective(...));
    }

    private function filterImportDirective($import)
    {
        return empty($import['import_condition'])
            || $this->getExpressionLanguage()->evaluate(
                $import['import_condition'],
                [
                    'container' => $this->container,
                    'import' => $import
                ]
            );
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!isset($this->expressionLanguage)) {
            $this->expressionLanguage = new ExpressionLanguage(null, [new ExpressionLanguageProvider()]);
        }

        return $this->expressionLanguage;
    }
}
