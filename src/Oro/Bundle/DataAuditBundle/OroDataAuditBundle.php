<?php

namespace Oro\Bundle\DataAuditBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass\EntityAuditStrategyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDataAuditBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityAuditStrategyPass());

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                    ['Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }
}
