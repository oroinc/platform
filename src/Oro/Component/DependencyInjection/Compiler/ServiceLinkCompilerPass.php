<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

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
            if (strpos($serviceId, '?') === 0) {
                $serviceId = substr($serviceId, 1);
                $isOptional = true;
            }

            if ($container->hasDefinition($serviceId)) {
                // the service we are referring to must be public
                $serviceDef = $container->getDefinition($serviceId);
                if (!$serviceDef->isPublic()) {
                    $serviceDef->setPublic(true);
                }
            } elseif ($container->hasAlias($serviceId)) {
                // the service alias we are referring to must be public
                $serviceAlias = $container->getAlias($serviceId);
                if (!$serviceAlias->isPublic()) {
                    $serviceAlias->setPublic(true);
                }
            } elseif (!$isOptional) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Target service "%s" is undefined. The service link "%s" with tag "%s" and tag-service "%s"',
                        $serviceId,
                        $id,
                        $this->tagName,
                        $serviceId
                    )
                );
            }

            $serviceLinkDef
                ->setClass($this->decoratorClass)
                ->setPublic(false)
                ->setArguments([new Reference('service_container'), $serviceId, $isOptional]);
        }
    }
}
