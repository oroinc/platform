<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
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
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var EntityDefinitionConfig $config */
        $config = $context->getResult();
        /** @var MetaPropertiesConfigExtra $configExtra */
        $configExtra = $context->getExtra(MetaPropertiesConfigExtra::NAME);
        $names = $configExtra->getMetaPropertyNames();
        foreach ($names as $name) {
            $dataType = $configExtra->getTypeOfMetaProperty($name);
            if (!$dataType) {
                continue;
            }
            $metaPropertyName = ConfigUtil::buildMetaPropertyName($name);
            if ($config->hasField($metaPropertyName)) {
                continue;
            }

            $field = $config->addField($metaPropertyName);
            $field->setMetaProperty(true);
            $field->setDataType($dataType);
            $field->setMetaPropertyResultName($name);
        }
    }
}
