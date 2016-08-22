<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('oro_layout.twig.form.engine')) {
            $this->changeService(
                $container,
                'twig.form.engine',
                'oro_layout.twig.form.engine'
            );
        }

        if ($container->has('oro_layout.templating.form.engine')) {
            $this->changeService(
                $container,
                'templating.form.engine',
                'oro_layout.templating.form.engine'
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceName
     * @param string $newServiceName
     */
    private function changeService(ContainerBuilder $container, $serviceName, $newServiceName)
    {
        $service = $container->getDefinition($serviceName);
        $newService = $container->getDefinition($newServiceName);

        $container->removeDefinition($serviceName);
        $container->setDefinition($serviceName, $newService);
    }
}
