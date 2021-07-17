<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
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
    /** @var AssociationManager */
    private $associationManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ValueTransformer */
    private $valueTransformer;

    public function __construct(
        AssociationManager $associationManager,
        DoctrineHelper $doctrineHelper,
        ValueTransformer $valueTransformer
    ) {
        $this->associationManager = $associationManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
                $data[$fieldName] = $this->buildExtendedAssociation($data, $entityClass, $dataType);
            }
        }

        return $data;
    }

    private function buildExtendedAssociation(array $data, string $entityClass, string $dataType): ?array
    {
        [$associationType, $associationKind] = DataType::parseExtendedAssociation($dataType);
        switch ($associationType) {
            case RelationType::MANY_TO_ONE:
                return $this->buildManyToOneExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind)
                );
            case RelationType::MANY_TO_MANY:
                return $this->buildManyToManyExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind)
                );
            case RelationType::MULTIPLE_MANY_TO_ONE:
                return $this->buildMultipleManyToOneExtendedAssociation(
                    $data,
                    $this->getAssociationTargets($entityClass, $associationType, $associationKind)
                );
            default:
                throw new \LogicException(sprintf(
                    'Unsupported type of extended association: %s.',
                    $associationType
                ));
        }
    }

    /**
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     *
     * @return array [target entity class => target field name, ...]
     */
    private function getAssociationTargets(
        string $entityClass,
        string $associationType,
        ?string $associationKind
    ): array {
        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);
        if ($resolvedEntityClass) {
            $entityClass = $resolvedEntityClass;
        }

        return $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );
    }

    /**
     * @param array $data
     * @param array $associationTargets [target entity class => target field name]
     *
     * @return array|null
     */
    private function buildManyToOneExtendedAssociation(array $data, array $associationTargets): ?array
    {
        $result = null;
        foreach ($associationTargets as $entityClass => $fieldName) {
            if (!empty($data[$fieldName])) {
                $result = $data[$fieldName];
                $result[ConfigUtil::CLASS_NAME] = $entityClass;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @param array $associationTargets [target entity class => target field name]
     *
     * @return array
     */
    private function buildManyToManyExtendedAssociation(array $data, array $associationTargets): array
    {
        $result = [];
        foreach ($associationTargets as $entityClass => $fieldName) {
            if (!empty($data[$fieldName])) {
                foreach ($data[$fieldName] as $item) {
                    $item[ConfigUtil::CLASS_NAME] = $entityClass;
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @param array $associationTargets [target entity class => target field name]
     *
     * @return array
     */
    private function buildMultipleManyToOneExtendedAssociation(array $data, array $associationTargets): array
    {
        $result = [];
        foreach ($associationTargets as $entityClass => $fieldName) {
            if (!empty($data[$fieldName])) {
                $item = $data[$fieldName];
                $item[ConfigUtil::CLASS_NAME] = $entityClass;
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
            if (false !== strpos($targetPropertyPath, ConfigUtil::PATH_DELIMITER)) {
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

    /**
     * @param mixed       $value
     * @param string|null $dataType
     *
     * @return bool
     */
    private function isEmptyValue($value, ?string $dataType): bool
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
