<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\ServiceLinkRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Gather all tagged with provided tag name services together as ServiceLinks into ServiceLinkRegistry
 * Injects registry to provided service
 */
class TaggedServiceLinkRegistryCompilerPass implements CompilerPassInterface
{
    /** @var string */
    private $linkedServicesTag;

    /** @var string */
    private $registryAwareServiceId;

    /** @var string */
    private $registryInjectionMethod;

    /**
     * @param string $tag
     * @param string $registryAwareServiceId
     * @param string $registryInjectionMethod defaults setServiceLinkRegistry
     * @see \Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface
     */
    public function __construct($tag, $registryAwareServiceId, $registryInjectionMethod = 'setServiceLinkRegistry')
    {
        $this->linkedServicesTag = $tag;
        $this->registryAwareServiceId = $registryAwareServiceId;
        $this->registryInjectionMethod = $registryInjectionMethod;
    }

    /**
     * @param ContainerBuilder $container
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->registryAwareServiceId)) {
            throw new \LogicException(
                sprintf('Service definition `%s` not found in container.', $this->registryAwareServiceId)
            );
        }

        $registryServiceId = $this->registryAwareServiceId . '.links_registry.' . $this->linkedServicesTag;

        if ($container->hasDefinition($registryServiceId)) {
            throw new \LogicException(
                sprintf(
                    'Only one injection of `%s` per service is currently supported.' .
                    'Trying to add `%1$s` to `%2$s` service by `%3$s` tag.',
                    ServiceLinkRegistry::class,
                    $this->registryAwareServiceId,
                    $this->linkedServicesTag
                )
            );
        }

        $registryService = $container->register($registryServiceId, ServiceLinkRegistry::class);
        $registryService->setArguments([new Reference('service_container')]);
        $registryService->setPublic(false);

        $tags = $container->findTaggedServiceIds($this->linkedServicesTag);

        foreach ($tags as $id => $tag) {
            $container->getDefinition($id)->setPublic(true);
            $alias = $id;
            if (isset($tag[0]['alias'])) {
                $alias = $tag[0]['alias'];
            }
            $registryService->addMethodCall('add', [$id, $alias]);
        }

        $container->getDefinition($this->registryAwareServiceId)
            ->addMethodCall($this->registryInjectionMethod, [new Reference($registryServiceId)]);
    }
}
