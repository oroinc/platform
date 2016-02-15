<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteDefinitionByExtra extends CompleteDefinition
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var array|null $definition */
        $definition = $context->getResult();
        if (empty($definition) || ConfigUtil::isExcludeAll($definition)) {
            // nothing to do
            return $definition;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return $definition;
        }

        $expandRelations = $context->get(ExpandRelatedEntitiesConfigExtra::NAME);

        $associations = $this->doctrineHelper->getEntityMetadataForClass($entityClass)->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (!$this->isAssociationCompletionRequired($fieldName, $definition)) {
                continue;
            }

            if (!in_array($fieldName, $expandRelations, true)) {
                continue;
            }

            $extras = array_filter(
                $context->getExtras(),
                function ($item) {
                    if ($item instanceof ExpandRelatedEntitiesConfigExtra
                        || $item instanceof FilterFieldsConfigExtra
                    ) {
                        return false;
                    };

                    return true;
                }
            );
            $config = $this->configProvider->getConfig(
                $mapping['targetEntity'],
                $context->getVersion(),
                $context->getRequestType(),
                $extras
            );

            $definition[ConfigUtil::FIELDS][$fieldName] = $config;
        }

        $context->setResult(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_NONE,
                ConfigUtil::FIELDS           => $definition[ConfigUtil::FIELDS]
            ]
        );
    }
}
