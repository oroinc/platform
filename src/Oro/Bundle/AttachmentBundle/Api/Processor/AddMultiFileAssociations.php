<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\AttachmentBundle\Api\MultiFileAssociationProvider;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds multi files and multi images associations to all entities that can have these associations.
 */
class AddMultiFileAssociations implements ProcessorInterface
{
    private const string SORT_ORDER = 'sortOrder';

    public function __construct(
        private readonly MultiFileAssociationProvider $multiFileAssociationProvider,
        private readonly ConfigProvider $configProvider,
        private readonly ResourcesProvider $resourcesProvider
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (ApiAction::OPTIONS === $targetAction) {
            return;
        }

        $associationName = $context->getAssociationName();
        if (!$associationName && $context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $definition = $context->getResult();
        $multiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $context->getClassName(),
            $version,
            $requestType
        );
        if ($multiFileAssociationNames) {
            $isReadonly = $this->resourcesProvider->isReadOnlyResource(File::class, $version, $requestType);
            $fileDefinition = $this->getFileDefinition($version, $requestType);
            foreach ($multiFileAssociationNames as $multiFileAssociationName) {
                $this->addMultiFileAssociation(
                    $targetAction,
                    $definition,
                    $multiFileAssociationName,
                    !$isReadonly,
                    $fileDefinition,
                    $isReadonly
                );
            }
        }
        if ($associationName && !$this->resourcesProvider->isReadOnlyResource(File::class, $version, $requestType)) {
            $parentEntityMultiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
                $context->getParentClassName(),
                $version,
                $requestType
            );
            if ($parentEntityMultiFileAssociationNames
                && \in_array($associationName, $parentEntityMultiFileAssociationNames, true)
            ) {
                /** @see BuildMultiFileSubresourceQuery */
                $sortOrderMetaProperty = $this->addSortOrderMetaProperty($definition, 'r.' . self::SORT_ORDER);
                if ($context->hasExtra(DescriptionsConfigExtra::NAME)) {
                    $sortOrderMetaProperty->setDescription(
                        'This meta option denotes which order a file is to appear when displayed.'
                    );
                }
            }
        }
    }

    private function addMultiFileAssociation(
        ?string $targetAction,
        EntityDefinitionConfig $definition,
        string $multiFileAssociationName,
        bool $addSortOrderMetaProperty,
        EntityDefinitionConfig $fileDefinition,
        bool $isReadonly
    ): void {
        $association = $definition->getOrAddField($multiFileAssociationName);
        $association->setTargetClass(File::class);
        $association->setTargetType(ConfigUtil::TO_MANY);
        if (ApiAction::CREATE !== $targetAction && ApiAction::UPDATE !== $targetAction) {
            $association->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        }
        $association->setDependsOn([
            $multiFileAssociationName . '.file',
            $multiFileAssociationName . '.' . self::SORT_ORDER
        ]);
        if ($isReadonly) {
            $association->setFormOption('mapped', false);
        }
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $this->addIdentifierDefinition($associationTargetEntity, $fileDefinition);
        if ($addSortOrderMetaProperty) {
            $sortOrderMetaProperty = $this->addSortOrderMetaProperty($associationTargetEntity, self::SORT_ORDER);
            $sortOrderMetaProperty->setAssociationLevelMetaProperty(true);
        }

        $sourceAssociation = $definition->addField(ConfigUtil::IGNORE_PROPERTY_PATH . $multiFileAssociationName);
        $sourceAssociation->setPropertyPath($multiFileAssociationName);
        $sourceAssociation->setExcluded();
        $sourceAssociationTargetEntity = $sourceAssociation->createAndSetTargetEntity();
        $sourceAssociationTargetEntity->setMaxResults(-1);
        $sourceAssociationTargetEntity->setOrderBy([self::SORT_ORDER => Criteria::ASC]);

        $definition->addField(ExtendConfigDumper::DEFAULT_PREFIX . $multiFileAssociationName)
            ->setExcluded();
    }

    private function addSortOrderMetaProperty(
        EntityDefinitionConfig $definition,
        string $propertyPath
    ): EntityDefinitionFieldConfig {
        $sortOrderMetaProperty = $definition->addField(ConfigUtil::buildMetaPropertyName(self::SORT_ORDER));
        $sortOrderMetaProperty->setMetaProperty(true);
        $sortOrderMetaProperty->setDataType(DataType::INTEGER);
        $sortOrderMetaProperty->setPropertyPath($propertyPath);
        $sortOrderMetaProperty->setMetaPropertyResultName(self::SORT_ORDER);

        return $sortOrderMetaProperty;
    }

    private function addIdentifierDefinition(
        EntityDefinitionConfig $definition,
        EntityDefinitionConfig $fileDefinition
    ): void {
        $identifierFieldNames = $fileDefinition->getIdentifierFieldNames();
        $definition->setIdentifierFieldNames($identifierFieldNames);
        foreach ($identifierFieldNames as $fieldName) {
            $definition->addField($fieldName)->setDataType($fileDefinition->getField($fieldName)->getDataType());
        }
    }

    private function getFileDefinition(string $version, RequestType $requestType): EntityDefinitionConfig
    {
        return $this->configProvider->getConfig(File::class, $version, $requestType, [
            new EntityDefinitionConfigExtra(),
            new FilterIdentifierFieldsConfigExtra()
        ])->getDefinition();
    }
}
