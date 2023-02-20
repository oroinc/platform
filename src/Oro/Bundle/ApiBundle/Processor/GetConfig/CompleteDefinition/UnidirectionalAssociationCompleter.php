<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Completes the configuration of fields with data-type equal to
 * "unidirectionalAssociation:{targetAssociationName}".
 * These fields are represented the inverse side of unidirectional associations.
 */
class UnidirectionalAssociationCompleter implements CustomDataTypeCompleterInterface
{
    public const UNIDIRECTIONAL_ASSOCIATIONS = 'unidirectional_associations';
    public const UNIDIRECTIONAL_ASSOCIATIONS_READONLY = 'unidirectional_associations_readonly';

    private const UNIDIRECTIONAL_ASSOCIATION_PREFIX = 'unidirectionalAssociation:';

    private DoctrineHelper $doctrineHelper;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function completeCustomDataType(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $dataType,
        string $version,
        RequestType $requestType
    ): bool {
        if (!str_starts_with($dataType, self::UNIDIRECTIONAL_ASSOCIATION_PREFIX)) {
            return false;
        }

        $formOptions = $field->getFormOptions();
        if ($formOptions && isset($formOptions['mapped']) && false === $formOptions['mapped']) {
            $readonlyAssociations = $definition->get(self::UNIDIRECTIONAL_ASSOCIATIONS_READONLY, []);
            $readonlyAssociations[] = $fieldName;
            $definition->set(self::UNIDIRECTIONAL_ASSOCIATIONS_READONLY, $readonlyAssociations);
        }

        $targetAssociationName = $this->completeUnidirectionalAssociation(
            $metadata,
            $field,
            $fieldName,
            $dataType,
            $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType)
        );

        $associations = $definition->get(self::UNIDIRECTIONAL_ASSOCIATIONS, []);
        $associations[$fieldName] = $targetAssociationName;
        $definition->set(self::UNIDIRECTIONAL_ASSOCIATIONS, $associations);

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function completeUnidirectionalAssociation(
        ClassMetadata $metadata,
        EntityDefinitionFieldConfig $field,
        string $fieldName,
        string $dataType,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): string {
        if (!$field->hasPropertyPath()) {
            $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        } elseif (ConfigUtil::IGNORE_PROPERTY_PATH !== $field->getPropertyPath()) {
            throw new \RuntimeException(sprintf(
                'The property path for the unidirectional association "%s::%s" must not be specified or must be "%s".',
                $metadata->name,
                $fieldName,
                ConfigUtil::IGNORE_PROPERTY_PATH
            ));
        }

        $targetClass = $field->getTargetClass();
        if ($targetClass) {
            $substituteClass = $entityOverrideProvider->getSubstituteEntityClass($targetClass);
            if ($substituteClass) {
                $field->setTargetClass($substituteClass);
            } else {
                $substitutedClass = $entityOverrideProvider->getEntityClass($targetClass);
                if ($substitutedClass) {
                    $targetClass = $substitutedClass;
                }
            }
        } else {
            throw new \RuntimeException(sprintf(
                'The target class for the unidirectional association "%s::%s" must be specified.',
                $metadata->name,
                $fieldName
            ));
        }
        if (!$this->doctrineHelper->isManageableEntityClass($targetClass)) {
            throw new \RuntimeException(sprintf(
                'The target class "%s" for the unidirectional association "%s::%s" must be a manageable entity.',
                $targetClass,
                $metadata->name,
                $fieldName
            ));
        }

        if (!$field->hasTargetType()) {
            $field->setTargetType(ConfigUtil::TO_MANY);
        } elseif (ConfigUtil::TO_MANY !== $field->getTargetType()) {
            throw new \RuntimeException(sprintf(
                'The target type for the unidirectional association "%s::%s" must not be specified or must be "%s".',
                $metadata->name,
                $fieldName,
                ConfigUtil::TO_MANY
            ));
        }

        $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
        $targetAssociationName = substr($dataType, \strlen(self::UNIDIRECTIONAL_ASSOCIATION_PREFIX));
        if (!$targetMetadata->hasAssociation($targetAssociationName)) {
            throw new \RuntimeException(sprintf(
                'The target entity "%s" for the unidirectional association "%s::%s" must have the association "%s".',
                $targetClass,
                $metadata->name,
                $fieldName,
                $targetAssociationName
            ));
        }

        $this->assetTargetAssociationMapping($metadata, $fieldName, $targetMetadata, $targetAssociationName);

        $field->setDataType(null);
        $field->setFormOption('mapped', false);
        $field->setAssociationQuery(
            $this->createAssociationQuery($metadata, $targetMetadata, $targetAssociationName)
        );

        return $targetAssociationName;
    }

    private function assetTargetAssociationMapping(
        ClassMetadata $metadata,
        string $fieldName,
        ClassMetadata $targetMetadata,
        string $targetAssociationName
    ): void {
        $targetAssociationMapping = $targetMetadata->getAssociationMapping($targetAssociationName);
        if (!$targetAssociationMapping['isOwningSide']) {
            throw new \RuntimeException(sprintf(
                'The association "%s::%s" that is referred by the unidirectional association "%s::%s"'
                . ' must be a owning side of the relation.',
                $targetMetadata->name,
                $targetAssociationName,
                $metadata->name,
                $fieldName
            ));
        }
        if ($targetAssociationMapping['sourceEntity'] !== $targetMetadata->name) {
            throw new \RuntimeException(sprintf(
                'The source entity of the association "%s::%s" that is referred by'
                . ' the unidirectional association "%s::%s" must be equal tp "%s".',
                $targetMetadata->name,
                $targetAssociationName,
                $metadata->name,
                $fieldName,
                $targetMetadata->name
            ));
        }
        if ($targetAssociationMapping['targetEntity'] !== $metadata->name) {
            throw new \RuntimeException(sprintf(
                'The source entity of the association "%s::%s" that is referred by'
                . ' the unidirectional association "%s::%s" must be equal tp "%s".',
                $targetMetadata->name,
                $targetAssociationName,
                $metadata->name,
                $fieldName,
                $metadata->name
            ));
        }
        if (!($targetAssociationMapping['type'] & (ClassMetadata::MANY_TO_ONE | ClassMetadata::MANY_TO_MANY))) {
            throw new \RuntimeException(sprintf(
                'The association "%s::%s" that is referred by the unidirectional association "%s::%s"'
                . ' must be many-to-one or many-to-many ORM association.',
                $targetMetadata->name,
                $targetAssociationName,
                $metadata->name,
                $fieldName
            ));
        }
    }

    private function createAssociationQuery(
        ClassMetadata $metadata,
        ClassMetadata $targetMetadata,
        string $targetAssociationName
    ): QueryBuilder {
        $targetEntityClass = $targetMetadata->name;
        $qb = $this->doctrineHelper->createQueryBuilder($metadata->name, 'e');
        if ($targetMetadata->isCollectionValuedAssociation($targetAssociationName)) {
            $qb->innerJoin(
                $targetEntityClass,
                'r',
                Join::WITH,
                sprintf('e MEMBER OF r.%s', $targetAssociationName)
            );
        } else {
            $qb->innerJoin(
                $targetEntityClass,
                'r',
                Join::WITH,
                sprintf('r.%s = e', $targetAssociationName)
            );
        }

        return $qb;
    }
}
