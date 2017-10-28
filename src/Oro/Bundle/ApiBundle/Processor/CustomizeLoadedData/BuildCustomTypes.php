<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Computes values of fields that represent
 * * extended associations
 * * nested objects
 * * nested associations
 */
class BuildCustomTypes implements ProcessorInterface
{
    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param AssociationManager $associationManager
     */
    public function __construct(AssociationManager $associationManager)
    {
        $this->associationManager = $associationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
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
    protected function processCustomTypes(array $data, EntityDefinitionConfig $config, $entityClass)
    {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (array_key_exists($fieldName, $data)) {
                continue;
            }
            $dataType = $field->getDataType();
            if (!$dataType) {
                continue;
            }

            if ($this->isNestedObject($dataType)) {
                $data[$fieldName] = $this->buildNestedObject($data, $field->getTargetEntity());
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
    protected function getAssociationOwnerPath(EntityDefinitionFieldConfig $field)
    {
        $propertyPath = $field->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        $lastDelimiter = strrpos($propertyPath, ConfigUtil::PATH_DELIMITER);
        if (false === $lastDelimiter) {
            return null;
        }

        return explode(ConfigUtil::PATH_DELIMITER, substr($propertyPath, 0, $lastDelimiter));
    }

    /**
     * @param array  $data
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array|null
     */
    protected function buildExtendedAssociation(
        array $data,
        $entityClass,
        $associationType,
        $associationKind
    ) {
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
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array [target entity class => target field name]
     */
    protected function getAssociationTargets($entityClass, $associationType, $associationKind)
    {
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
    protected function buildManyToOneExtendedAssociation(array $data, array $associationTargets)
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
    protected function buildManyToManyExtendedAssociation(array $data, array $associationTargets)
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
    protected function buildMultipleManyToOneExtendedAssociation(array $data, array $associationTargets)
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
    protected function isNestedObject($dataType)
    {
        return DataType::isNestedObject($dataType) || DataType::isNestedAssociation($dataType);
    }

    /**
     * @param array                  $data
     * @param EntityDefinitionConfig $config
     *
     * @return array|null
     */
    protected function buildNestedObject(array $data, EntityDefinitionConfig $config)
    {
        $result = [];
        $isEmpty = true;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $value = $this->getOwnPropertyValue($data, $field->getPropertyPath($fieldName));
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
    protected function getOwnPropertyValue(array $data, $propertyPath)
    {
        if (false !== strpos($propertyPath, ConfigUtil::PATH_DELIMITER)) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" property path is not supported.',
                    $propertyPath
                )
            );
        }

        $result = null;
        if (array_key_exists($propertyPath, $data)) {
            $result = $data[$propertyPath];
        }

        return $result;
    }

    /**
     * @param array  $data
     * @param string $propertyPath
     *
     * @return mixed
     */
    protected function getChildData(array $data, $propertyPath)
    {
        $result = $data;
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        foreach ($path as $fieldName) {
            if (is_array($result) && array_key_exists($fieldName, $result)) {
                $result = $result[$fieldName];
            } else {
                $result = null;
                break;
            }
        }

        return $result;
    }
}
