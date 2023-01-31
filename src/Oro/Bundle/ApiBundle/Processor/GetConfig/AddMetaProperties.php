<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the configuration for all meta properties requested via "meta" filter.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleMetaPropertyFilter
 */
class AddMetaProperties implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        /** @var MetaPropertiesConfigExtra $configExtra */
        $configExtra = $context->getExtra(MetaPropertiesConfigExtra::NAME);
        $names = $configExtra->getMetaPropertyNames();
        foreach ($names as $name) {
            $dataType = $configExtra->getTypeOfMetaProperty($name);
            if (!$dataType) {
                continue;
            }
            $metaPropertyName = ConfigUtil::buildMetaPropertyName($name);
            if ($definition->hasField($metaPropertyName)) {
                continue;
            }

            $field = $definition->addField($metaPropertyName);
            $field->setMetaProperty(true);
            $field->setDataType($dataType);
            $field->setMetaPropertyResultName($name);
        }
    }
}
