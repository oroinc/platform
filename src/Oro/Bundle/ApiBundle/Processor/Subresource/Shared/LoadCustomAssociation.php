<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * The base class for processors that load data for different kind of custom associations.
 */
abstract class LoadCustomAssociation implements ProcessorInterface
{
    protected EntitySerializer $entitySerializer;
    protected DoctrineHelper $doctrineHelper;
    protected EntityIdHelper $entityIdHelper;
    private ConfigProvider $configProvider;

    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        ConfigProvider $configProvider
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $associationName = $context->getAssociationName();
        $dataType = $this->findFieldDataType($context->getParentConfig(), $associationName);
        if ($dataType && $this->isSupportedAssociation($dataType)) {
            $this->loadAssociationData($context, $associationName, $dataType);
        }
    }

    abstract protected function isSupportedAssociation(string $dataType): bool;

    abstract protected function loadAssociationData(
        SubresourceContext $context,
        string $associationName,
        string $dataType
    ): void;

    protected function saveAssociationDataToContext(SubresourceContext $context, mixed $data): void
    {
        $context->setResult($data);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    protected function loadData(SubresourceContext $context, string $associationName, bool $isCollection): ?array
    {
        return $this->getAssociationData(
            $this->loadParentEntityData($context),
            $associationName,
            $isCollection
        );
    }

    protected function loadParentEntityData(SubresourceContext $context): ?array
    {
        $data = $this->entitySerializer->serialize(
            $this->getQueryBuilder(
                $context->getParentClassName(),
                $context->getParentId(),
                $context->getParentMetadata()
            ),
            $this->getLoadParentEntityDataConfig($context),
            $context->getNormalizationContext()
        );

        return $data ? reset($data) : null;
    }

    protected function getLoadParentEntityDataConfig(SubresourceContext $context): EntityDefinitionConfig
    {
        $configExtras = TargetConfigExtraBuilder::buildParentConfigExtras(
            $context->getConfigExtras(),
            $context->getParentClassName(),
            $context->getAssociationName()
        );
        $config = $this->configProvider
            ->getConfig(
                $context->getParentClassName(),
                $context->getVersion(),
                $context->getRequestType(),
                $configExtras
            )
            ->getDefinition();
        TargetConfigExtraBuilder::normalizeParentConfig(
            $config,
            $context->getAssociationName(),
            $configExtras
        );

        return $config;
    }

    protected function getAssociationData(mixed $parentEntityData, string $associationName, bool $isCollection): ?array
    {
        if (empty($parentEntityData) || !\array_key_exists($associationName, $parentEntityData)) {
            return $isCollection ? [] : null;
        }

        $result = $parentEntityData[$associationName];
        if (!$isCollection && null !== $result && empty($result)) {
            $result = null;
        }

        return $result;
    }

    protected function getQueryBuilder(
        string $parentEntityClass,
        mixed $parentEntityId,
        EntityMetadata $parentEntityMetadata
    ): QueryBuilder {
        $query = $this->doctrineHelper->createQueryBuilder($parentEntityClass, 'e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $query,
            $parentEntityId,
            $parentEntityMetadata
        );

        return $query;
    }

    protected function isCollection(string $associationType): bool
    {
        switch ($associationType) {
            case RelationType::MANY_TO_ONE:
                return false;
            case RelationType::MANY_TO_MANY:
            case RelationType::MULTIPLE_MANY_TO_ONE:
                return true;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported type of extended association: %s.',
                    $associationType
                ));
        }
    }

    /**
     * Finds the data-type of the given field.
     * If the "data_type" attribute is not defined for the field,
     * but the field has the "property_path" attribute the data-type of the target field is returned.
     */
    protected function findFieldDataType(EntityDefinitionConfig $config, string $fieldName): ?string
    {
        $field = $config->findField($fieldName);
        if (null === $field) {
            return null;
        }

        $dataType = $field->getDataType();
        if (!$dataType) {
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath) {
                $targetField = $config->findFieldByPath($propertyPath, true);
                if (null !== $targetField) {
                    $dataType = $targetField->getDataType();
                }
            }
        }

        return $dataType;
    }
}
