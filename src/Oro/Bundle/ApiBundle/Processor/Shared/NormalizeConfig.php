<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeConfig implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $config = $context->getConfig();
        if (empty($config)) {
            // nothing to normalize
            return;
        }

        $this->normalizeConfig($config);
        $context->setConfig($config);
    }

    /**
     * @param array $config
     */
    protected function normalizeConfig(array &$config)
    {
        if (isset($config[ConfigUtil::FIELDS]) && is_array($config[ConfigUtil::FIELDS])) {
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => &$fieldConfig) {
                if (is_array($fieldConfig) && array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
                    $fieldConfig = $fieldConfig[ConfigUtil::DEFINITION];
                    if (is_array($fieldConfig)) {
                        $this->normalizeConfig($fieldConfig);
                    }
                }
            }
        }
    }
}
