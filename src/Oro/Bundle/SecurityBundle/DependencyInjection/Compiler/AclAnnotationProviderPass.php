<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads ACL annotation loaders into annotation provider.
 */
class AclAnnotationProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    const PROVIDER_SERVICE_NAME = 'oro_security.acl.annotation_provider';
    const TAG_NAME              = 'oro_security.acl.config_loader';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::PROVIDER_SERVICE_NAME)) {
            return;
        }

        $taggedServices = $this->findAndSortTaggedServices(self::TAG_NAME, $container);

        if ($taggedServices) {
            $providerDef = $container->getDefinition(self::PROVIDER_SERVICE_NAME);
            foreach ($taggedServices as $loader) {
                $providerDef->addMethodCall('addLoader', [$loader]);
            }
        }
    }
}
