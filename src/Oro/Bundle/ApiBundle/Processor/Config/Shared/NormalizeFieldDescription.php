<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeFieldDescription extends NormalizeDescription
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)
            || empty($definition[ConfigUtil::FIELDS])
            || !is_array($definition[ConfigUtil::FIELDS])
        ) {
            // a configuration of fields does not exist or a normalization is not needed
            return;
        }

        $fields = array_keys($definition[ConfigUtil::FIELDS]);
        foreach ($fields as $fieldName) {
            $fieldConfig = $definition[ConfigUtil::FIELDS][$fieldName];
            if (null !== $fieldConfig && is_array($fieldConfig)) {
                $this->normalizeAttribute($fieldConfig, ConfigUtil::LABEL);
                $this->normalizeAttribute($fieldConfig, ConfigUtil::DESCRIPTION);
                $definition[ConfigUtil::FIELDS][$fieldName] = $fieldConfig;
            }
        }

        $context->setResult($definition);
    }
}
