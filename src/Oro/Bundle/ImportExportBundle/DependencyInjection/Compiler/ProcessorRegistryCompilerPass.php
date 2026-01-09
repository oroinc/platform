<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers import/export processors with the processor registry.
 *
 * This pass collects all services tagged with `oro_importexport.processor`,
 * validates that each tag contains required attributes (type, entity, alias),
 * and registers the processors with the registry. This enables the system to locate
 * the appropriate processor for a given entity, operation type, and alias combination.
 */
class ProcessorRegistryCompilerPass implements CompilerPassInterface
{
    public const PROCESSOR_REGISTRY_SERVICE = 'oro_importexport.processor.registry';
    public const PROCESSOR_TAG              = 'oro_importexport.processor';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $processors = $this->findProcessorTaggedServiceIds($container);

        $registryDefinition = $container->getDefinition(self::PROCESSOR_REGISTRY_SERVICE);

        foreach ($processors as $processorId => $processorTags) {
            foreach ($processorTags as $processorTag) {
                $registryDefinition->addMethodCall(
                    'registerProcessor',
                    array(
                        new Reference($processorId),
                        $processorTag['type'],
                        $processorTag['entity'],
                        $processorTag['alias']
                    )
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return array
     */
    protected function findProcessorTaggedServiceIds(ContainerBuilder $container)
    {
        $processors = $container->findTaggedServiceIds(self::PROCESSOR_TAG);

        foreach ($processors as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $this->assertTagHasAttributes(
                    $serviceId,
                    self::PROCESSOR_TAG,
                    $tag,
                    array('type', 'entity', 'alias')
                );

                $type = $tag['type'];

                if (!isset($result[$type])) {
                    $result[$type] = array();
                }
                if (!isset($result[$type][$serviceId])) {
                    $result[$type][$serviceId] = array();
                }

                $result[$type][$serviceId][] = $tag;
            }
        }

        return $processors;
    }

    /**
     * @param string $serviceId
     * @param string $tagName
     * @param array $tagAttributes
     * @param array $requiredAttributes
     * @throws LogicException
     */
    private function assertTagHasAttributes($serviceId, $tagName, array $tagAttributes, array $requiredAttributes)
    {
        foreach ($requiredAttributes as $attribute) {
            if (empty($tagAttributes[$attribute])) {
                throw new LogicException(
                    sprintf('Tag "%s" for service "%s" must have attribute "%s"', $tagName, $serviceId, $attribute)
                );
            }
        }
    }
}
