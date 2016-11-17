<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds inverse side of "to-one" extended association.
 */
class AddInverseToOneAssociation implements ProcessorInterface
{
    /** @var string */
    protected $associationClass;

    /** @var string */
    protected $associationKind;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ConfigProvider  $configProvider
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(
        ConfigProvider $configProvider,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->extendConfigProvider = $configProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * Sets the extend association source class name.
     *
     * @param string $associationClass
     */
    public function setAssociationClass($associationClass)
    {
        $this->associationClass = $associationClass;
    }

    /**
     * Sets the extend association kind.
     *
     * @param string $associationKind
     */
    public function setAssociationKind($associationKind)
    {
        $this->associationKind = $associationKind;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if (!$this->associationClass) {
            // association class was not set to service
            return;
        }

        $entityClass = $context->getClassName();

        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        if (!$this->extendConfigProvider->hasConfig($this->associationClass)) {
            // only configurable entities are supported
            return;
        }

        $field = new EntityDefinitionFieldConfig();
        $field->setDataType(
            DataType::buildExtendedInverseAssociation(
                $this->associationClass,
                RelationType::MANY_TO_ONE,
                $this->associationKind
            )
        );

        $definition = $context->getResult();
        $extendConfig = $this->extendConfigProvider->getConfig($this->associationClass);
        $relations = $extendConfig->get('relation', false, []);

        $fieldName = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $this->associationClass,
            $context->getRequestType()
        );

        foreach ($relations as $relationData) {
            if (!$this->isSupportedRelation($relationData, $entityClass)) {
                continue;
            }

            $definition->addField($fieldName, $field);

            break;
        }

        $context->setResult($definition);
    }

    /**
     * @param array $relationData
     *
     * @return bool
     */
    protected function isSupportedRelation(array $relationData, $targetClass)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $relationData['field_id'];

        return
            $fieldConfigId instanceof FieldConfigId
            && $fieldConfigId->getFieldType() === RelationType::MANY_TO_ONE
            && $relationData['target_entity'] === $targetClass
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relationData['target_entity'],
                $this->associationKind
            );
    }
}
