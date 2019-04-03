<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Defines a mechanism for adding and extending system variables that can be used in sandboxed TWIG templates.
 */
interface SystemVariablesProviderInterface
{
    /**
     * Gets definitions of variables available in sandboxed TWIG templates.
     *
     * @return array [entity class => [variable name => definition, ...], ...]
     *               The definition attributes:
     *                  'type'  => variable data type
     *                  'label' => translated variable name
     */
    public function getVariableDefinitions(): array;

    /**
     * Gets values of variables available in sandboxed TWIG templates.
     *
     * @return array [variable name => variable value, ...]
     */
    public function getVariableValues(): array;
}
