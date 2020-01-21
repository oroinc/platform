<?php

namespace Oro\Bundle\EntityConfigBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The EntityConfigBundle bundle class.
 */
class OroEntityConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\EntityConfigPass);
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_entity_config.registry.attribute_type',
            'oro_entity_config.attribute_type',
            'type'
        ));

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                ['Oro\Bundle\EntityConfigBundle\Audit\Entity'],
                [$this->getPath() . DIRECTORY_SEPARATOR . 'Audit' . DIRECTORY_SEPARATOR . 'Entity']
            )
        );

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                ['Oro\Bundle\EntityConfigBundle\Attribute\Entity'],
                [$this->getPath() . DIRECTORY_SEPARATOR . 'Attribute' . DIRECTORY_SEPARATOR . 'Entity']
            )
        );

        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily' => [
                'label' => 'labels'
            ],
            'Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup' => [
                'label' => 'labels'
            ],
        ]));
    }
}
