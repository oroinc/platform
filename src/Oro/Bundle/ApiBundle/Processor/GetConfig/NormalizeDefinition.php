<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\FieldConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var FieldConfigProvider */
    protected $fieldConfigProvider;

    /** @var RelationConfigProvider */
    protected $relationConfigProvider;

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param FieldConfigProvider    $fieldConfigProvider
     * @param RelationConfigProvider $relationConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FieldConfigProvider $fieldConfigProvider,
        RelationConfigProvider $relationConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->fieldConfigProvider    = $fieldConfigProvider;
        $this->relationConfigProvider = $relationConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var array|null $definition */
        $definition = $context->getResult();
        if (empty($definition)) {
            // nothing to normalize
            return;
        }

        $fields = ConfigUtil::getFields($definition);

        if (!ConfigUtil::isExcludeAll($definition)) {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntity($entityClass)) {
                $fields = $this->completeDefinition(
                    $fields,
                    $entityClass,
                    $context->getVersion(),
                    $context->getRequestType(),
                    $context->getRequestAction()
                );
            }
        }

        $context->setResult(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                ConfigUtil::FIELDS           => $fields
            ]
        );
    }

    /**
     * @param array  $definition
     * @param string $entityClass
     * @param string $version
     * @param string $requestType
     * @param string $requestAction
     *
     * @return array
     */
    protected function completeDefinition(
        array $definition,
        $entityClass,
        $version,
        $requestType,
        $requestAction
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $definition = $this->getFields($definition, $metadata, $version, $requestType, $requestAction);
        $definition = $this->getAssociations($definition, $metadata, $version, $requestType, $requestAction);

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     * @param string        $requestType
     * @param string        $requestAction
     *
     * @return array
     */
    protected function getFields(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $requestAction
    ) {
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $definition[$fieldName] = $this->fieldConfigProvider->getFieldConfig(
                $metadata->name,
                $fieldName,
                $version,
                $requestType,
                $requestAction
            );
        }

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     * @param string        $requestType
     * @param string        $requestAction
     *
     * @return array
     */
    protected function getAssociations(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $requestAction
    ) {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $definition[$fieldName] = $this->relationConfigProvider->getRelationConfig(
                $mapping['targetEntity'],
                $fieldName,
                $version,
                $requestType,
                $requestAction
            );
        }

        return $definition;
    }
}
