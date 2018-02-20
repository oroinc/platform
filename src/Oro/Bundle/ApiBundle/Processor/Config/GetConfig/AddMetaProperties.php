<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds configuration for all meta properties requested via "meta" filter.
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
            $metaPropertyName = ConfigUtil::buildMetaPropertyName($name);
            if (!$config->hasField($metaPropertyName)) {
                $field = $config->addField($metaPropertyName);
                $field->setMetaProperty(true);
                $field->setDataType($configExtra->getTypeOfMetaProperty($name));
                $field->setMetaPropertyResultName($name);
            }
        }
    }
}
