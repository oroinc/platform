<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Configuration;

class ConfigurationPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_ID = 'oro_query_designer.query_designer.manager';
    const TAG_NAME           = 'oro_filter.extension.orm_filter.filter';
    const CONFIG_FILE_NAME   = 'query_designer.yml';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MANAGER_SERVICE_ID)) {
            $managerDef = $container->getDefinition(self::MANAGER_SERVICE_ID);

            $configs = array();
            foreach ($container->getParameter('kernel.bundles') as $bundle) {
                $reflection = new \ReflectionClass($bundle);
                $file       = dirname($reflection->getFilename()) . '/Resources/config/' . self::CONFIG_FILE_NAME;
                if (is_file($file)) {
                    $configs[] = Yaml::parse(realpath($file))[Configuration::ROOT_NODE_NAME];
                }
            }

            $filterTypes = [];
            $filters     = $container->findTaggedServiceIds(self::TAG_NAME);
            foreach ($filters as $serviceId => $tags) {
                $attr = reset($tags);
                $managerDef->addMethodCall('addFilter', array($attr['type'], new Reference($serviceId)));
                $filterTypes[] = $attr['type'];
            }

            $processor = new Processor();
            $config    = $processor->processConfiguration(new Configuration($filterTypes), $configs);
            $managerDef->replaceArgument(0, $config);
        }
    }
}
