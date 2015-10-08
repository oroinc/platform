<?php

namespace Oro\Bundle\EntityConfigBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceMethodPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\EntityConfigPass;

class OroEntityConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ServiceMethodPass);
        $container->addCompilerPass(new EntityConfigPass);

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                ['Oro\Bundle\EntityConfigBundle\Audit\Entity'],
                [__DIR__ . DIRECTORY_SEPARATOR . 'Audit' . DIRECTORY_SEPARATOR . 'Entity']
            )
        );
    }
}
