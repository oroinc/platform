<?php

namespace Oro\Bundle\ConfigBundle\Config\ApiTree;

class SectionDefinition
{
    /** @var string */
    protected $name;

    /** @var VariableDefinition[] */
    protected $variables = [];

    /** @var SectionDefinition[] */
    protected $subSections = [];

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $key
     *
     * @return VariableDefinition|null
     */
    public function getVariable($key)
    {
        if (!isset($this->variables[$key])) {
            return null;
        }

        return $this->variables[$key];
    }

    /**
     * @param boolean $deep
     * @param boolean $useVariableKeyAsArrayKey
     *
     * @return VariableDefinition[]
     */
    public function getVariables($deep = false, $useVariableKeyAsArrayKey = false)
    {
        /** @var VariableDefinition[] $variables */
        $variables = $this->variables;

        if ($deep) {
            foreach ($this->subSections as $subSection) {
                $variables = array_merge($variables, $subSection->getVariables($deep, true));
            }
        }

        return $useVariableKeyAsArrayKey
            ? $variables
            : array_values($variables);
    }

    /**
     * @param VariableDefinition $variable
     */
    public function addVariable(VariableDefinition $variable)
    {
        $this->variables[$variable->getKey()] = $variable;
    }

    /**
     * @param string $name
     *
     * @return SectionDefinition|null
     */
    public function getSubSection($name)
    {
        if (!isset($this->subSections[$name])) {
            return null;
        }

        return $this->subSections[$name];
    }

    /**
     * @param boolean $deep
     *
     * @return SectionDefinition[]
     */
    public function getSubSections($deep = false)
    {
        /** @var SectionDefinition[] $result */
        $result = array_values($this->subSections);

        if ($deep) {
            foreach ($this->subSections as $subSection) {
                $result = array_merge($result, $subSection->getSubSections($deep));
            }
        }

        return $result;
    }

    /**
     * @param SectionDefinition $subSection
     */
    public function addSubSection(SectionDefinition $subSection)
    {
        $this->subSections[$subSection->getName()] = $subSection;
    }
}
