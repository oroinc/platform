<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sets the metadata factory and reflection service for ORM Entity Manager
 */
class EntityManagerPass implements CompilerPassInterface
{
    public const ORM_METADATA_FACTORY_SERVICE_KEY    = 'oro_entity_extend.orm.metadata_factory';
    public const ORM_METADATA_REFLECTION_SERVICE_KEY = 'oro_entity_extend.orm.metadata_reflection_service';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $em = $container->findDefinition('doctrine.orm.entity_manager');
        $em->addMethodCall('setMetadataFactory', [new Reference(self::ORM_METADATA_FACTORY_SERVICE_KEY)]);
        $em->addMethodCall('setMetadataReflectionService', [new Reference(self::ORM_METADATA_REFLECTION_SERVICE_KEY)]);
    }
}
