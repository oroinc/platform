<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\DependencyInjection\Compiler;

use Oro\Bundle\InstallerBundle\Provider\ReadOnlyConnectionAwareInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Compiler pass to inject read-only connection names into ReadOnlyConnectionAwareInterface services.
 */
class ReadOnlyConnectionAwareCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('oro_installer.database.readonly')) {
            return;
        }

        $readonlyConnections = $container->getParameter('oro_installer.database.readonly');

        foreach ($container->getDefinitions() as $definition) {
            if ($definition->isAbstract()) {
                continue;
            }

            $className = $this->resolveClassName($container, $definition);
            if (!$className || !$this->isReadOnlyConnectionAwareClass($className)) {
                continue;
            }

            $definition->addMethodCall('setReadOnlyConnections', [$readonlyConnections]);
        }
    }

    /**
     * Some third-party service classes can fail during autoload (e.g. optional templating dependencies).
     * Those definitions must be skipped instead of breaking container compilation.
     */
    private function isReadOnlyConnectionAwareClass(string $className): bool
    {
        try {
            return is_a($className, ReadOnlyConnectionAwareInterface::class, true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveClassName(ContainerBuilder $container, Definition $definition): ?string
    {
        $className = $definition->getClass();
        if (!$className && $definition instanceof ChildDefinition) {
            $parentId = $definition->getParent();
            if ($parentId && $container->hasDefinition($parentId)) {
                return $this->resolveClassName($container, $container->getDefinition($parentId));
            }

            return null;
        }

        if (!$className) {
            return null;
        }

        $className = $container->getParameterBag()->resolveValue($className);

        return is_string($className) ? $className : null;
    }
}
