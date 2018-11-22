<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\DataType;
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
 */
class BuildCustomTypes implements ProcessorInterface
{
    /** @var AssociationManager */
    private $associationManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AssociationManager $associationManager
     * @param DoctrineHelper     $doctrineHelper
     */
    public function __construct(AssociationManager $associationManager, DoctrineHelper $doctrineHelper)
    {
        $this->associationManager = $associationManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!\is_array($data)) {
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $context->setResult($this->processCustomTypes($data, $config, $context->getClassName()));
    }

    /**
     * @param array                  $data
     * @param EntityDefinitionConfig $config
     * @param string                 $entityClass
     *
     * @return array
     */
    private function processCustomTypes(array $data, EntityDefinitionConfig $config, string $entityClass): array
    {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (\array_key_exists($fieldName, $data)) {
                continue;
            }
            $dataType = $field->getDataType();
            if (!$dataType) {
                continue;
            }

            if ($this->isNestedObject($dataType)) {
                $data[$fieldName] = $this->buildNestedObject($data, $field->getTargetEntity(), $config);
            } elseif (DataType::isExtendedAssociation($dataType)) {
                list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
                $associationOwnerPath = $this->getAssociationOwnerPath($field);
                if ($associationOwnerPath) {
                    $associationOwnerField = $config->findFieldByPath($associationOwnerPath, true);
                    if ($associationOwnerField && $associationOwnerField->getTargetClass()) {
                        $data[$fieldName] = $this->buildExtendedAssociation(
                            $this->getChildData($data, $associationOwnerPath),
                            $associationOwnerField->getTargetClass(),
                            $associationType,
                            $associationKind
                        );
                    }
                } else {
                    $data[$fieldName] = $this->buildExtendedAssociation(
                        $data,
                        $entityClass,
                        $associationType,
                        $associationKind
                    );
                }
            }
        }

        return $data;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     *
     * @return string[]|null
     */
    private function getAssociationOwnerPath(EntityDefinitionFieldConfig $field)
    {
        $propertyPath = $field->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        $lastDelimiter = \strrpos($propertyPath, ConfigUtil::PATH_DELIMITER);
        if (false === $lastDelimiter) {
            return null;
        }

        return \explode(ConfigUtil::PATH_DELIMITER, \substr($propertyPath, 0, $lastDelimiter));
    }

    /**
     * @param array       $data
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     *
     * @return array|null
     */
    private function buildExtendedAssociation(array $data, $entityClass, $associationType, $associationKind)
    {
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
                throw new \LogicException(
                    sprintf('Unsupported type of extended association: %s.', $associationType)
                );
        }
    }

    /**
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     *
     * @return array [target entity class => target field name, ...]
     */
    private function getAssociationTargets($entityClass, $associationType, $associationKind)
    {
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
    private function buildManyToOneExtendedAssociation(array $data, array $associationTargets)
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
    private function buildManyToManyExtendedAssociation(array $data, array $associationTargets)
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
    private function buildMultipleManyToOneExtendedAssociation(array $data, array $associationTargets)
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

    /**
     * @param string $dataType
     *
     * @return bool
     */
    private function isNestedObject($dataType)
    {
        return DataType::isNestedObject($dataType) || DataType::isNestedAssociation($dataType);
    }

    /**
     * @param array                  $data
     * @param EntityDefinitionConfig $config
     * @param EntityDefinitionConfig $parentConfig
     *
     * @return array|null
     */
    private function buildNestedObject(
        array $data,
        EntityDefinitionConfig $config,
        EntityDefinitionConfig $parentConfig
    ) {
        $result = [];
        $isEmpty = true;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $value = null;
            $targetPropertyPath = $field->getPropertyPath($fieldName);
            if (false !== \strpos($targetPropertyPath, ConfigUtil::PATH_DELIMITER)) {
                throw new RuntimeException(
                    \sprintf('The "%s" property path is not supported.', $targetPropertyPath)
                );
            }
            $targetFieldName = $parentConfig->findFieldNameByPropertyPath($targetPropertyPath);
            if ($targetFieldName && \array_key_exists($targetFieldName, $data)) {
                $value = $data[$targetFieldName];
            }
            if (null !== $value) {
                $isEmpty = false;
            }
            $result[$fieldName] = $value;
        }

        return $isEmpty ? null : $result;
    }

    /**
     * @param array  $data
     * @param string $propertyPath
     *
     * @return mixed
     */
    private function getChildData(array $data, $propertyPath)
    {
        $result = $data;
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        foreach ($path as $fieldName) {
            if (\is_array($result) && \array_key_exists($fieldName, $result)) {
                $result = $result[$fieldName];
            } else {
                $result = null;
                break;
            }
        }

        return $result;
    }
}
