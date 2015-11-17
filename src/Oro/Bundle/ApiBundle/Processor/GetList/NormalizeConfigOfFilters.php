<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeConfigOfFilters implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (null === $configOfFilters) {
            // a filters' configuration does not exist
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // an entity configuration does not exist
            return;
        }

        $this->collectFilters($configOfFilters, $config);
        $context->setConfigOfFilters($configOfFilters);
    }

    /**
     * @param array       $configOfFilters
     * @param array       $config
     * @param string|null $fieldPrefix
     *
     * @return array
     */
    protected function collectFilters(array &$configOfFilters, array $config, $fieldPrefix = null)
    {
        if (isset($config[ConfigUtil::FIELDS]) && is_array($config[ConfigUtil::FIELDS])) {
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                if (null !== $fieldPrefix) {
                    $field = $fieldPrefix . $fieldName;
                    if (!isset($configOfFilters[ConfigUtil::FIELDS][$field])) {
                        $configOfFilters[ConfigUtil::FIELDS][$field] = $fieldConfig;
                    }
                }
                if (array_key_exists(ConfigUtil::FILTERS, $fieldConfig)) {
                    $this->collectFilters(
                        $configOfFilters,
                        $fieldConfig[ConfigUtil::FILTERS],
                        (null !== $fieldPrefix ? $fieldPrefix . $fieldName : $fieldName) . '.'
                    );
                }
            }
        }
    }
}
