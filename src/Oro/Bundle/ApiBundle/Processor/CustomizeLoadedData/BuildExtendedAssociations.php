<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Computes values of fields that represent extended associations.
 */
class BuildExtendedAssociations implements ProcessorInterface
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
            if (!$field->isExcluded()
                && DataType::isExtendedAssociation($field->getDataType())
                && !array_key_exists($fieldName, $data)
            ) {
                list($associationType, $associationKind) = DataType::parseExtendedAssociation(
                    $field->getDataType()
                );
                $associationTargets = $this->associationManager->getAssociationTargets(
                    $context->getClassName(),
                    null,
                    $associationType,
                    $associationKind
                );
                $data[$fieldName] = $this->buildExtendedAssociation(
                    $data,
                    $associationType,
                    $associationTargets
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
     * @param string $associationType
     * @param array  $associationTargets [target entity class => target field name]
     *
     * @return array|null
     */
    protected function buildExtendedAssociation(
        array $data,
        $associationType,
        array $associationTargets
    ) {
        switch ($associationType) {
            case RelationType::MANY_TO_ONE:
                return $this->buildManyToOneExtendedAssociation($data, $associationTargets);
            case RelationType::MANY_TO_MANY:
                return $this->buildManyToManyExtendedAssociation($data, $associationTargets);
            case RelationType::MULTIPLE_MANY_TO_ONE:
                return $this->buildMultipleManyToOneExtendedAssociation($data, $associationTargets);
            default:
                throw new \LogicException(
                    sprintf('Unsupported type of extended association: %s.', $associationType)
                );
        }
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
}
