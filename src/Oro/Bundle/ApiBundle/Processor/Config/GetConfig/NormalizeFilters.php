<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\Criteria;

class NormalizeFilters implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (null === $filters) {
            // a filters' configuration does not exist
            return;
        }

        $definition = $context->getResult();
        if (null === $definition) {
            // an entity configuration does not exist
            return;
        }

        $this->collectFilters($filters, $definition);
        $context->setFilters($filters);
    }

    /**
     * @param array       $filters
     * @param array       $definition
     * @param string|null $fieldPrefix
     *
     * @return array
     */
    protected function collectFilters(array &$filters, array $definition, $fieldPrefix = null)
    {
        if (isset($definition[ConfigUtil::FIELDS]) && is_array($definition[ConfigUtil::FIELDS])) {
            foreach ($definition[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                if (null !== $fieldPrefix) {
                    $field = $fieldPrefix . $fieldName;
                    if (!isset($filters[ConfigUtil::FIELDS][$field])) {
                        $filters[ConfigUtil::FIELDS][$field] = $fieldConfig;
                    }
                }
                if (array_key_exists(ConfigUtil::FILTERS, $fieldConfig)) {
                    $this->collectFilters(
                        $filters,
                        $fieldConfig[ConfigUtil::FILTERS],
                        (null !== $fieldPrefix ? $fieldPrefix . $fieldName : $fieldName) . Criteria::FIELD_DELIMITER
                    );
                }
            }
        }
    }
}
