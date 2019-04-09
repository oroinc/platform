<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Represents processors that resolve values of variables for sandboxed TWIG templates.
 */
interface VariableProcessorInterface
{
    /**
     * Resolves the given variable.
     * The resolved value should be added to the $data using setComputedVariable() method.
     * If a value cannot be resolved due to some error, the error should be logged
     * and NULL value should be set to the $data.
     *
     * How to assign a processor to a variable:
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface::getVariableProcessors
     *
     * How to add a computed value to the template data:
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData::setComputedVariable
     *
     * How to get the path to the parent variable:
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData::getParentVariablePath
     *
     * How to get the value of an entity related variable:
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData::getEntityVariable
     *
     * Note: if a variable defined by EntityVariablesProviderInterface::getVariableDefinitions
     * contains dots, they will be replaced with underscore (_) before passing to a processor.
     * For example if the variable name is "url.view", it will be passes to a processor as "url_view".
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface::getVariableDefinitions
     *
     * @param string       $variable           The path to the variable
     * @param array        $processorArguments The arguments that are provided by
     *                                         EntityVariablesProviderInterface::getVariableProcessors
     * @param TemplateData $data               The object that contains all data
     *                                         that will be passed to the TWIG template
     */
    public function process(string $variable, array $processorArguments, TemplateData $data): void;
}
