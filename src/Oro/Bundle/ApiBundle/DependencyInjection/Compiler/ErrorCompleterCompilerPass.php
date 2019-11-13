<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all error completers.
 */
class ErrorCompleterCompilerPass implements CompilerPassInterface
{
    private const ERROR_COMPLETER_REGISTRY_SERVICE_ID = 'oro_api.error_completer_registry';
    private const ERROR_COMPLETER_TAG                 = 'oro.api.error_completer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $errorCompleters = [];
        $taggedServices = $container->findTaggedServiceIds(self::ERROR_COMPLETER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $errorCompleters[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        if ($errorCompleters) {
            $errorCompleters = DependencyInjectionUtil::sortByPriorityAndFlatten($errorCompleters);
        }

        $container->getDefinition(self::ERROR_COMPLETER_REGISTRY_SERVICE_ID)
            ->setArgument(0, $errorCompleters)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
