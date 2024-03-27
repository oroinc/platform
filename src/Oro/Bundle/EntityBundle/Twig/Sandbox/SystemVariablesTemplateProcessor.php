<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Parses the given TWIG template and adds filters to the system variables.
 */
class SystemVariablesTemplateProcessor
{
    private string $systemSection = 'system';

    private string $pathSeparator = '.';

    /** @var array [variable path => filter, ...] */
    private array $systemVariableDefaultFilters = [];

    public function setSystemSection(string $systemSection): void
    {
        $this->systemSection = $systemSection;
    }

    public function setPathSeparator(string $pathSeparator): void
    {
        $this->pathSeparator = $pathSeparator;
    }

    /**
     * Adds TWIG filter that should be applied to the given system variable if not any other filter is applied to it.
     */
    public function addSystemVariableDefaultFilter(string $variable, string $filter): void
    {
        $this->systemVariableDefaultFilters[$this->systemSection . $this->pathSeparator . $variable] = $filter;
    }

    public function processSystemVariables(string $templateContent): string
    {
        foreach ($this->systemVariableDefaultFilters as $var => $filter) {
            $templateContent = \preg_replace(
                '/{{\s' . $var . '\s}}/u',
                \sprintf('{{ %s|%s }}', $var, $filter),
                $templateContent
            );
        }

        return $templateContent;
    }
}
