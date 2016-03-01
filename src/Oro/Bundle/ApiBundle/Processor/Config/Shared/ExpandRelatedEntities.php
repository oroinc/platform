<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads full configuration of the target entity for associations were requested to expand.
 * For example, in JSON.API the "include" parameter can be used to request related resources.
 */
class ExpandRelatedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $configProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if ($definition->isExcludeAll()) {
            // already processed
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->completeAssociations(
            $definition,
            $entityClass,
            $context->get(ExpandRelatedEntitiesConfigExtra::NAME),
            $context->getVersion(),
            $context->getRequestType(),
            $this->getAssociationExtras($context->getExtras())
        );
    }

    /**
     * @param array $extras
     *
     * @return array
     */
    protected function getAssociationExtras($extras)
    {
        return array_values(
            array_filter(
                $extras,
                function ($extra) {
                    return
                        !$extra instanceof ExpandRelatedEntitiesConfigExtra
                        && !$extra instanceof FilterFieldsConfigExtra;
                }
            )
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string[]               $associationNames
     * @param string                 $version
     * @param string[]               $requestType
     * @param ConfigExtraInterface[] $associationExtras
     */
    protected function completeAssociations(
        EntityDefinitionConfig $definition,
        $entityClass,
        $associationNames,
        $version,
        array $requestType,
        array $associationExtras
    ) {
        $metadata     = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (!in_array($fieldName, $associationNames, true)) {
                continue;
            }

            $config = $this->configProvider->getConfig(
                $mapping['targetEntity'],
                $version,
                $requestType,
                $associationExtras
            );
            if ($config->hasDefinition()) {
                $targetEntity = $config->getDefinition();
                foreach ($associationExtras as $extra) {
                    $sectionName = $extra->getName();
                    if ($extra instanceof ConfigExtraSectionInterface && $config->has($sectionName)) {
                        $targetEntity->set($sectionName, $config->get($sectionName));
                    }
                }
                $definition->getOrAddField($fieldName)->setTargetEntity($targetEntity);
            }
        }
    }
}
