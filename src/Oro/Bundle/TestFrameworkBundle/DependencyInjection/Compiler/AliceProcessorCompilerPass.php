<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all Alice processors that can be used in data fixtures for functional tests.
 */
class AliceProcessorCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            'oro_test.alice_fixture_loader',
            'oro_test.alice_processor',
            'addProcessor'
        );
    }
}
