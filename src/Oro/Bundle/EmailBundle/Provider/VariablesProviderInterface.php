<?php

namespace Oro\Bundle\EmailBundle\Provider;

interface VariablesProviderInterface
{
    /**
     * Gets variables available in a template
     *
     * @param array $context The contextual information
     *                       For example the contextual information can contain info about an entity,
     *                       template type etc.
     *
     * @return array The list of variables in the following format:
     *               {scope name} => [
     *                  {variable path} => [
     *                      'type'   => {variable data type}
     *                      'name'   => {translated variable name}
     *                      'getter' => {method name} // optional
     *                  ]
     *               ]
     *               The variable path should be compatible with Symfony PropertyAccess Component
     */
    public function getTemplateVariables(array $context = []);
}
