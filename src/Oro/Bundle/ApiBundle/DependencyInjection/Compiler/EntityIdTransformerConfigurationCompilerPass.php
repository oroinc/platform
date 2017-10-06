<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services tagged by "oro.api.entity_id_transformer" tag.
 */
class EntityIdTransformerConfigurationCompilerPass implements CompilerPassInterface
{
    const TRANSFORMER_REGISTRY_SERVICE_ID = 'oro_api.entity_id_transformer_registry';
    const TRANSFORMER_TAG                 = 'oro.api.entity_id_transformer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find entity id transformers
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $transformers[$this->getAttribute($tagAttributes, 'priority', 0)][] = [
                    new Reference($id),
                    $this->getAttribute($tagAttributes, 'requestType', null)
                ];
            }
        }
        if (empty($transformers)) {
            return;
        }

        // sort by priority and flatten
        krsort($transformers);
        $transformers = call_user_func_array('array_merge', $transformers);

        // register
        $container->getDefinition(self::TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $transformers);
    }

    /**
     * @param array  $attributes
     * @param string $attributeName
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    private function getAttribute(array $attributes, $attributeName, $defaultValue)
    {
        if (!array_key_exists($attributeName, $attributes)) {
            return $defaultValue;
        }

        return $attributes[$attributeName];
    }
}
