<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of fields that represent
 * * extended associations
 * * nested objects
 * * nested associations
 * * percentage value multiplied by 100 (the "percent_100" data type)
 */
class BuildCustomTypes implements ProcessorInterface
{
    private ExtendedAssociationProvider $extendedAssociationProvider;
    private DoctrineHelper $doctrineHelper;
    private ValueTransformer $valueTransformer;

    public function __construct(
        ExtendedAssociationProvider $extendedAssociationProvider,
        DoctrineHelper $doctrineHelper,
        ValueTransformer $valueTransformer
    ) {
        $this->extendedAssociationProvider = $extendedAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $context->setData($this->processCustomTypes(
            $data,
            $config,
            $context->getClassName(),
            $context->getNormalizationContext()
        ));
    }

    private function processCustomTypes(
        array $data,
        EntityDefinitionConfig $config,
        string $entityClass,
        array $context
    ): array {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $dataType = $field->getDataType();
            if (!$dataType) {
                continue;
            }

            if (\array_key_exists($fieldName, $data)) {
                if (DataType::PERCENT_100 === $dataType && null !== $data[$fieldName]) {
                    $data[$fieldName] /= 100.0;
                }
            } elseif ($this->isNestedObject($dataType)) {
                $data[$fieldName] = $this->buildNestedObject($data, $field->getTargetEntity(), $config, $context);
            } elseif (DataType::isExtendedAssociation($dataType)) {
                $data[$fieldName] = $this->buildExtendedAssociation(
                    $data,
                    $entityClass,
                    $dataType,
                    $field->getDependsOn()
                );
            }
        }

        return $data;
    }

    private function buildExtendedAssociation(
        array $data,
        string $entityClass,
        string $dataType,
        ?array $targetFieldNames
    ): ?array {
        [$associationType, $associationKind] = DataType::parseExtendedAssociation($dataType);
        switch ($associationType) {
            case RelationType::MANY_TO_ONE:
                return $this->buildManyToOneExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind, $targetFieldNames)
                );
            case RelationType::MANY_TO_MANY:
                return $this->buildManyToManyExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind, $targetFieldNames)
                );
            case RelationType::MULTIPLE_MANY_TO_ONE:
                return $this->buildMultipleManyToOneExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind, $targetFieldNames)
                );
            default:
                throw new \LogicException(sprintf(
                    'Unsupported type of extended association: %s.',
                    $associationType
                ));
        }
    }

    /**
     * @param string        $entityClass
     * @param string        $associationType
     * @param string|null   $associationKind
     * @param string[]|null $targetFieldNames
     *
     * @return array [target entity class => target field name, ...]
     */
    private function getAssociationTargets(
        string $entityClass,
        string $associationType,
        ?string $associationKind,
        ?array $targetFieldNames
    ): array {
        if (!$targetFieldNames) {
            return [];
        }

        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);
        if ($resolvedEntityClass) {
            $entityClass = $resolvedEntityClass;
        }

        return $this->extendedAssociationProvider->filterExtendedAssociationTargets(
            $entityClass,
            $associationType,
            $associationKind,
            $targetFieldNames
        );
    }

    private function buildManyToOneExtendedAssociation(array $data, array $associationTargets): ?array
    {
        $result = null;
        foreach ($associationTargets as $targetClass => $targetField) {
            if (!empty($data[$targetField])) {
                $result = $data[$targetField];
                $result[ConfigUtil::CLASS_NAME] = $targetClass;
                break;
            }
        }

        return $result;
    }

    private function buildManyToManyExtendedAssociation(array $data, array $associationTargets): array
    {
        $result = [];
        foreach ($associationTargets as $targetClass => $targetField) {
            if (!empty($data[$targetField])) {
                foreach ($data[$targetField] as $item) {
                    $item[ConfigUtil::CLASS_NAME] = $targetClass;
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    private function buildMultipleManyToOneExtendedAssociation(array $data, array $associationTargets): array
    {
        $result = [];
        foreach ($associationTargets as $targetClass => $targetField) {
            if (!empty($data[$targetField])) {
                $item = $data[$targetField];
                $item[ConfigUtil::CLASS_NAME] = $targetClass;
                $result[] = $item;
            }
        }

        return $result;
    }

    private function isNestedObject(string $dataType): bool
    {
        return DataType::isNestedObject($dataType) || DataType::isNestedAssociation($dataType);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildNestedObject(
        array $data,
        EntityDefinitionConfig $config,
        EntityDefinitionConfig $parentConfig,
        array $context
    ): ?array {
        $result = [];
        $isEmpty = true;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $value = null;
            $targetPropertyPath = $field->getPropertyPath($fieldName);
            if (str_contains($targetPropertyPath, ConfigUtil::PATH_DELIMITER)) {
                throw new RuntimeException(sprintf(
                    'The "%s" property path is not supported.',
                    $targetPropertyPath
                ));
            }
            $targetFieldName = $parentConfig->findFieldNameByPropertyPath($targetPropertyPath);
            if ($targetFieldName && \array_key_exists($targetFieldName, $data)) {
                $value = $data[$targetFieldName];
            }
            if (null !== $value && $this->isEmptyValue($value, $field->getDataType())) {
                $value = null;
            }
            if (null !== $value) {
                $isEmpty = false;
                $value = $this->valueTransformer->transformFieldValue($value, $field->toArray(true), $context);
            }
            $result[$fieldName] = $value;
        }

        return $isEmpty ? null : $result;
    }

    private function isEmptyValue(mixed $value, ?string $dataType): bool
    {
        if (null === $dataType || DataType::STRING === $dataType) {
            return '' === $value;
        }
        if (DataType::OBJECT === $dataType || DataType::isArray($dataType)) {
            return [] === $value;
        }

        return false;
    }
}
