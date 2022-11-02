<?php

namespace Oro\Bundle\CurrencyBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCurrencyBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigSandboxConfigurationPass());

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                    ['Oro\Bundle\CurrencyBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }
}
