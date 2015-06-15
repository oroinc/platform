<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

class ServiceLinkPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_service_link';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tags = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($tags as $id => $tag) {
            /** @var Definition $serviceLinkDef */
            $serviceLinkDef = $container->getDefinition($id);

            if (!isset($tag[0]['service'])) {
                throw new RuntimeException(
                    sprintf('Tag "%s" for service "%s" does not have required param "service"', self::TAG_NAME, $id)
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
                throw new RuntimeException(
                    sprintf(
                        'Target service "%s" is undefined. The service link "%s" with tag "%s" and tag-service "%s"',
                        $serviceId,
                        $id,
                        self::TAG_NAME,
                        $serviceId
                    )
                );
            }

            $serviceLinkDef
                ->setClass('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
                ->setPublic(false)
                ->setArguments([new Reference('service_container'), $serviceId, $isOptional]);
        }
    }
}
