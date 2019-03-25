<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Defines a mechanism for adding and extending entity variables that can be used in sandboxed TWIG templates.
 */
interface EntityVariablesProviderInterface
{
    /**
     * Gets definitions of variables available in sandboxed TWIG templates.
     *
     * @param string|null $entityClass The FQCN of an entity.
     *                                 If it is not specified the definitions for all entities are returned.
     *
     * @return array The list of variables in the following format:
     *                  variable name => array
     *                      'type'  => variable data type
     *                      'label' => translated variable name
     *               If a field represents a relation:
     *                      'related_entity_name' => FQCN of related entity
     *               If $entityClass is NULL variables are grouped by entity class:
     *                  entity class => array
     *                      variable name => array of attributes described above
     */
    public function getVariableDefinitions(string $entityClass = null): array;

    /**
     * Gets processors for variables available in sandboxed TWIG templates.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface
     *
     * @param string $entityClass The FQCN of an entity.
     *
     * @return array The list of variables in the following format:
     *                  variable name => array
     *                      'processor' => processor name
     *               Also any additional attributes required to a specific processor are allowed,
     *               they will be passed to "process()" method.
     */
    public function getVariableProcessors(string $entityClass): array;

    /**
     * Gets getters of variables available in sandboxed TWIG templates.
     *
     * @return array [entity class => [variable name => method, ...], ...]
     *               The method can be:
     *                  a string contains the method name
     *                  NULL if an entity field is public
     *                  an array with the following attributes:
     *                      'property_path'     => method name or NULL
     *                  If a field value should be formatted before rendering
     *                  (a formatter is instance of Oro\Bundle\UIBundle\Formatter\FormatterInterface;
     *                  the default formatter is used if no any other formatter is applied):
     *                      'default_formatter' => formatter name or [formatter name, formatter parameters]
     */
    public function getVariableGetters(): array;
}
