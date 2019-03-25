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
     * @param string       $variable
     * @param array        $processorArguments
     * @param TemplateData $data
     */
    public function process(string $variable, array $processorArguments, TemplateData $data): void;
}
