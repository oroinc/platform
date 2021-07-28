<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finalize the definitions of the service links.
 */
class ServiceLinkCompilerPass implements CompilerPassInterface
{
    /** @var string */
    private $tagName;

    /** @var string */
    private $decoratorClass;

    /**
     * @param string $tagName
     * @param string $decoratorClass
     */
    public function __construct(
        $tagName = 'service_link',
        $decoratorClass = 'Oro\Component\DependencyInjection\ServiceLink'
    ) {
        $this->tagName        = $tagName;
        $this->decoratorClass = $decoratorClass;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $locator = $container->getDefinition('oro_platform.service_link.service_locator');
        $services = [];

        $tags = $container->findTaggedServiceIds($this->tagName);
        foreach ($tags as $id => $tag) {
            /** @var Definition $serviceLinkDef */
            $serviceLinkDef = $container->getDefinition($id);

            if (!isset($tag[0]['service'])) {
                throw new InvalidArgumentException(
                    sprintf('Tag "%s" for service "%s" does not have required param "service"', $this->tagName, $id)
                );
            }

            $serviceId = $tag[0]['service'];
            $isOptional = false;
            if (str_starts_with($serviceId, '?')) {
                $serviceId = substr($serviceId, 1);
                $isOptional = true;
            }

            $services[] = new Reference(
                $serviceId,
                $isOptional
                    ? ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                    : ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
            );

            $serviceLinkDef
                ->setClass($this->decoratorClass)
                ->setPublic(false)
                ->setArguments([new Reference('oro_platform.service_link.service_locator'), $serviceId, $isOptional]);
        }

        $locator->replaceArgument(0, $services);
    }
}
