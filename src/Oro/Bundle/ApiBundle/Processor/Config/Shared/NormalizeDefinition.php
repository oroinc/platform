<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeDefinition implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)) {
            // nothing to normalize
            return;
        }

        $this->normalizeDefinition($definition);
        $context->setResult($definition);
    }

    /**
     * @param array $definition
     */
    protected function normalizeDefinition(array &$definition)
    {
        if (isset($definition[ConfigUtil::FIELDS]) && is_array($definition[ConfigUtil::FIELDS])) {
            foreach ($definition[ConfigUtil::FIELDS] as $fieldName => &$fieldConfig) {
                if (is_array($fieldConfig) && array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
                    $fieldConfig = $fieldConfig[ConfigUtil::DEFINITION];
                    if (is_array($fieldConfig)) {
                        $this->normalizeDefinition($fieldConfig);
                    }
                }
            }
        }
    }
}
