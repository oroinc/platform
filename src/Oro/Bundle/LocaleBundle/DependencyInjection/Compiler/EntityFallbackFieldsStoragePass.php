<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers default fallback fields mapping.
 */
class EntityFallbackFieldsStoragePass implements CompilerPassInterface
{
    private array $classes;

    /**
     * @param array $classes [class name => [singular field name => field name, ...], ...]
     */
    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $storage = $container->getDefinition('oro_locale.storage.entity_fallback_fields_storage');
        $fieldMap = $storage->getArgument(0);
        foreach ($this->classes as $class => $fields) {
            if (!$fields) {
                continue;
            }
            if (empty($fieldMap[$class])) {
                $fieldMap[$class] = $fields;
            } else {
                $fieldMap[$class] = array_merge($fieldMap[$class], $fields);
            }
        }
        $storage->setArgument(0, $fieldMap);
    }
}
