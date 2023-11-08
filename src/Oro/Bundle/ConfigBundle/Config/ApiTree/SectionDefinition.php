<?php

namespace Oro\Bundle\ConfigBundle\Config\ApiTree;

/**
 * The definition of a section in API configuration tree.
 */
class SectionDefinition
{
    private string $name;
    /** @var VariableDefinition[] [variable key => variable definition, ...] */
    private array $variables = [];
    /** @var SectionDefinition[] [section name => section definition, ...] */
    private array $subSections = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVariable(string $key): ?VariableDefinition
    {
        return $this->variables[$key] ?? null;
    }

    /**
     * @return VariableDefinition[]
     */
    public function getVariables(): array
    {
        return array_values($this->variables);
    }

    public function addVariable(VariableDefinition $variable): void
    {
        $this->variables[$variable->getKey()] = $variable;
    }

    public function getSubSection(string $name): ?SectionDefinition
    {
        return $this->subSections[$name] ?? null;
    }

    /**
     * @return SectionDefinition[]
     */
    public function getSubSections(): array
    {
        return array_values($this->subSections);
    }

    public function addSubSection(SectionDefinition $subSection): void
    {
        $this->subSections[$subSection->getName()] = $subSection;
    }
}
