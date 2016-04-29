<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Bundle\SoapBundle\ServiceDefinition\Loader\FilterableLoaderInterface;

class LoadPass implements CompilerPassInterface
{
    const LOADER_TAG = 'besimple.soap.definition.loader';
    const LOADER_FILTER_TAG = 'oro_soap.definition.loader_filter';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_soap',
            new YamlCumulativeFileLoader('Resources/config/oro/soap.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $classes = array_merge($classes, $resource->data['classes']);
        }

        $container
            ->getDefinition('oro_soap.loader')
            ->addArgument(array_unique($classes));

        // inject property filters into loaders that support it
        $this->inject($container, self::LOADER_FILTER_TAG, self::LOADER_TAG, 'addTypeFilter');
    }

    /**
     * Inject services marked with $sourceServiceTag tag
     * into services marked with $targetServiceTag tag
     * using $injectMethod of $targetServiceTag service
     *
     * @param ContainerBuilder $container
     * @param string           $sourceServiceTag
     * @param string           $targetServiceTag
     * @param string           $injectMethod
     */
    protected function inject(ContainerBuilder $container, $sourceServiceTag, $targetServiceTag, $injectMethod)
    {
        $definitionLoaderFilters = $container->findTaggedServiceIds($sourceServiceTag);
        if (empty($definitionLoaderFilters)) {
            return;
        }

        foreach ($container->findTaggedServiceIds($targetServiceTag) as $id => $attributes) {
            $definition = $container->getDefinition($id);

            $className = $definition->getClass();
            if ($className[0] == '%') {
                $className = str_replace('%', '', $definition->getClass());
            }

            if ($container->hasParameter($className)) {
                $className = $container->getParameter($className);
            }

            if (is_a($className, 'Oro\Bundle\SoapBundle\ServiceDefinition\Loader\FilterableLoaderInterface', true)) {
                foreach ($definitionLoaderFilters as $filterId => $tags) {
                    $definition->addMethodCall($injectMethod, [new Reference($filterId)]);
                }
            }
        }
    }
}
