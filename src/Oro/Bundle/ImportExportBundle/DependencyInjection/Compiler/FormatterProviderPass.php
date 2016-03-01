<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormatterProviderPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_importexport.formatter.formatter_provider';
    const TAG_NAME   = 'oro_importexport.formatter.formatter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $providerDefinition = $container->getDefinition(self::SERVICE_ID);

        $formatterIds   = [];
        $typeFormatters = [];

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            if (!$definition->isPublic()) {
                throw new \InvalidArgumentException(
                    sprintf('The service "%s" tagged "%s" must be public.', $serviceId, self::TAG_NAME)
                );
            }
            foreach ($tags as $tag) {
                if (isset($tag['alias'])) {
                    $formatterIds[$tag['alias']] = $serviceId;
                }
                if (isset($tag['data_type'])) {
                    if (!isset($tag['format_type'])) {
                        throw new \InvalidArgumentException(
                            sprintf('"format_type" tag attribute must be defined for service "%s"', $serviceId)
                        );
                    }
                    $typeFormatters[$tag['format_type']][$tag['data_type']] = $serviceId;
                }
            }
        }

        $providerDefinition->replaceArgument(1, $formatterIds);
        $providerDefinition->replaceArgument(2, $typeFormatters);
    }
}
