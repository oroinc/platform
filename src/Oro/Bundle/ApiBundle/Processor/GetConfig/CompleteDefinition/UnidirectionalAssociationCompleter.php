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

    #[\Override]
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
            throw new \RuntimeException(\sprintf(
                'The property path for the unidirectional association "%s::%s" must not be specified or must be "%s".',
                $metadata->getName(),
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
            throw new \RuntimeException(\sprintf(
                'The target class for the unidirectional association "%s::%s" must be specified.',
                $metadata->getName(),
                $fieldName
            ));
        }
        if (!$this->doctrineHelper->isManageableEntityClass($targetClass)) {
            throw new \RuntimeException(\sprintf(
                'The target class "%s" for the unidirectional association "%s::%s" must be a manageable entity.',
                $targetClass,
                $metadata->getName(),
                $fieldName
            ));
        }

        $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
        $targetAssociationName = substr($dataType, \strlen(self::UNIDIRECTIONAL_ASSOCIATION_PREFIX));
        if (!$targetMetadata->hasAssociation($targetAssociationName)) {
            throw new \RuntimeException(\sprintf(
                'The target entity "%s" for the unidirectional association "%s::%s" must have the association "%s".',
                $targetClass,
                $metadata->getName(),
                $fieldName,
                $targetAssociationName
            ));
        }

        $this->assetTargetAssociationMapping($metadata, $fieldName, $targetMetadata, $targetAssociationName);

        $targetAssociationType = $targetMetadata->getAssociationMapping($targetAssociationName)['type'];
        $targetType = $targetAssociationType & ClassMetadata::ONE_TO_ONE
            ? ConfigUtil::TO_ONE
            : ConfigUtil::TO_MANY;
        if (!$field->hasTargetType()) {
            $field->setTargetType($targetType);
        } elseif ($targetType !== $field->getTargetType()) {
            throw new \RuntimeException(\sprintf(
                'The target type for the unidirectional association "%s::%s" must not be specified or must be "%s".',
                $metadata->getName(),
                $fieldName,
                $targetType
            ));
        }

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
            throw new \RuntimeException(\sprintf(
                'The association "%s::%s" that is referred by the unidirectional association "%s::%s"'
                . ' must be a owning side of the relation.',
                $targetMetadata->getName(),
                $targetAssociationName,
                $metadata->getName(),
                $fieldName
            ));
        }
        if ($targetAssociationMapping['sourceEntity'] !== $targetMetadata->getName()) {
            throw new \RuntimeException(\sprintf(
                'The source entity of the association "%s::%s" that is referred by'
                . ' the unidirectional association "%s::%s" must be equal tp "%s".',
                $targetMetadata->getName(),
                $targetAssociationName,
                $metadata->getName(),
                $fieldName,
                $targetMetadata->getName()
            ));
        }
        if ($targetAssociationMapping['targetEntity'] !== $metadata->getName()) {
            throw new \RuntimeException(\sprintf(
                'The source entity of the association "%s::%s" that is referred by'
                . ' the unidirectional association "%s::%s" must be equal tp "%s".',
                $targetMetadata->getName(),
                $targetAssociationName,
                $metadata->getName(),
                $fieldName,
                $metadata->getName()
            ));
        }
        if (!($targetAssociationMapping['type'] & (ClassMetadata::TO_ONE | ClassMetadata::MANY_TO_MANY))) {
            throw new \RuntimeException(\sprintf(
                'The association "%s::%s" that is referred by the unidirectional association "%s::%s"'
                . ' must be one-to-one, many-to-one or many-to-many ORM association.',
                $targetMetadata->getName(),
                $targetAssociationName,
                $metadata->getName(),
                $fieldName
            ));
        }
    }

    private function createAssociationQuery(
        ClassMetadata $metadata,
        ClassMetadata $targetMetadata,
        string $targetAssociationName
    ): QueryBuilder {
        $targetAssociationType = $targetMetadata->getAssociationMapping($targetAssociationName)['type'];
        if ($targetAssociationType & ClassMetadata::MANY_TO_MANY) {
            return $this->doctrineHelper->createQueryBuilder($metadata->getName(), 'e')
                ->innerJoin(
                    $targetMetadata->getName(),
                    'r',
                    Join::WITH,
                    \sprintf('e MEMBER OF r.%s', $targetAssociationName)
                );
        }

        if ($targetAssociationType & ClassMetadata::MANY_TO_ONE) {
            return $this->doctrineHelper->createQueryBuilder($metadata->getName(), 'e')
                ->innerJoin(
                    $targetMetadata->getName(),
                    'r',
                    Join::WITH,
                    \sprintf('r.%s = e', $targetAssociationName)
                );
        }

        return $this->doctrineHelper->createQueryBuilder($targetMetadata->getName(), 'r')
            ->innerJoin(
                $metadata->getName(),
                'e',
                Join::WITH,
                \sprintf('r.%s = e', $targetAssociationName)
            );
    }
}
