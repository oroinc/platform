<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConverterFormattersPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_importexport.converter.formatter_provider';
    const TAG_NAME   = 'oro_importexport.converter.formatter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $providerDefinition = $container->getDefinition(self::SERVICE_ID);

        $formatterIds      = [];
        $defaultFormatters = [];

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            if (!$definition->isPublic()) {
                $message = sprintf(
                    'The service "%s" tagged "%s" must be public.',
                    $serviceId,
                    self::TAG_NAME
                );
                throw new \InvalidArgumentException($message);
            }
            foreach ($tags as $tag) {
                if (isset($tag['alias'])) {
                    $formatterIds[$tag['alias']] = $serviceId;
                }
                if (isset($tag['type'])) {
                    $defaultFormatters[$tag['type']] = $serviceId;
                }
            }
        }

        $providerDefinition->replaceArgument(1, $formatterIds);
        $providerDefinition->replaceArgument(2, $defaultFormatters);
    }
}
