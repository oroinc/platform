<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
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

        $hasChanges = false;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $dataType = $field->getDataType();
            if (DataType::isNestedObject($dataType) || DataType::isNestedAssociation($dataType)) {
                $data[$fieldName] = $this->buildNestedObject($data, $field->getTargetEntity());
                $hasChanges = true;
            } elseif (DataType::isExtendedAssociation($dataType) && !array_key_exists($fieldName, $data)) {
                list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
                $data[$fieldName] = $this->buildExtendedAssociation(
                    $data,
                    $context->getClassName(),
                    $associationType,
                    $associationKind
                );
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $context->setResult($data);
        }
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
        if (false !== strpos($propertyPath, '.')) {
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
}
