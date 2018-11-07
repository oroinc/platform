<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class provides methods to find CompilerPasses
 */
trait CompilerPassProviderTrait
{
    /**
     * Finds CompilerPass by class name from all passes for the BeforeOptimization pass
     *
     * @param ContainerBuilder $container
     * @param string $className
     *
     * @return CompilerPassInterface|null
     */
    private function findCompilerPassByClassName(ContainerBuilder $container, string $className)
    {
        $result = null;
        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        foreach ($passes as $pass) {
            if ($pass instanceof $className) {
                $result = $pass;
                break;
            }
        }

        return $result;
    }
}
