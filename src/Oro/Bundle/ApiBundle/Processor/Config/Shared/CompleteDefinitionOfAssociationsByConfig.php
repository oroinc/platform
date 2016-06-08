<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads configuration from the "relations" section of "Resources/config/oro/api.yml"
 * for all associations that were not configured yet.
 */
class CompleteDefinitionOfAssociationsByConfig implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RelationConfigProvider */
    protected $relationConfigProvider;

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param RelationConfigProvider $relationConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RelationConfigProvider $relationConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->relationConfigProvider = $relationConfigProvider;
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
            $context->getVersion(),
            $context->getRequestType(),
            $context->getPropagableExtras()
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function completeAssociations(
        EntityDefinitionConfig $definition,
        $entityClass,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $metadata     = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $propertyPath => $mapping) {
            $field = $definition->findField($propertyPath, true);
            if (null !== $field && $field->hasTargetEntity()) {
                continue;
            }

            $config = $this->relationConfigProvider->getRelationConfig(
                $mapping['targetEntity'],
                $version,
                $requestType,
                $extras
            );
            if ($config->hasDefinition()) {
                $targetEntity = $config->getDefinition();
                foreach ($extras as $extra) {
                    $sectionName = $extra->getName();
                    if ($extra instanceof ConfigExtraSectionInterface && $config->has($sectionName)) {
                        $targetEntity->set($sectionName, $config->get($sectionName));
                    }
                }

                if (null === $field) {
                    $field = $definition->addField($propertyPath);
                }
                if ($targetEntity->isCollapsed()) {
                    $field->setCollapsed();
                    $targetEntity->setCollapsed(false);
                }
                $field->setTargetEntity($targetEntity);
            }
        }
    }
}
