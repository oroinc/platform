<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\FieldConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class CompleteDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var FieldConfigProvider */
    protected $fieldConfigProvider;

    /** @var RelationConfigProvider */
    protected $relationConfigProvider;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param FieldConfigProvider        $fieldConfigProvider
     * @param RelationConfigProvider     $relationConfigProvider
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FieldConfigProvider $fieldConfigProvider,
        RelationConfigProvider $relationConfigProvider,
        ExclusionProviderInterface $exclusionProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->fieldConfigProvider    = $fieldConfigProvider;
        $this->relationConfigProvider = $relationConfigProvider;
        $this->exclusionProvider      = $exclusionProvider;
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

        $fields = ConfigUtil::getArrayValue($definition, ConfigUtil::FIELDS);

        if (!ConfigUtil::isExcludeAll($definition)) {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $fields = $this->completeDefinition(
                    $fields,
                    $entityClass,
                    $context->getVersion(),
                    $context->getRequestType(),
                    $context->getExtras()
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
     * @param array    $definition
     * @param string   $entityClass
     * @param string   $version
     * @param string   $requestType
     * @param string[] $extras
     *
     * @return array
     */
    protected function completeDefinition(
        array $definition,
        $entityClass,
        $version,
        $requestType,
        $extras
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $definition = $this->getFields($definition, $metadata, $version, $requestType, $extras);
        $definition = $this->getAssociations($definition, $metadata, $version, $requestType, $extras);

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     * @param string        $requestType
     * @param string[]      $extras
     *
     * @return array
     */
    protected function getFields(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $extras
    ) {
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $config = $this->fieldConfigProvider->getFieldConfig(
                $metadata->name,
                $fieldName,
                $version,
                $requestType,
                $extras
            );

            if ($this->exclusionProvider->isIgnoredField($metadata, $fieldName)) {
                $config[ConfigUtil::DEFINITION][ConfigUtil::EXCLUDE] = true;
            }
            $definition[$fieldName] = $config;
        }

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     * @param string        $requestType
     * @param string[]      $extras
     *
     * @return array
     */
    protected function getAssociations(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $extras
    ) {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (array_key_exists($fieldName, $definition)
                && (
                    !is_array($definition[$fieldName])
                    || ConfigUtil::isRelationInitialized($definition[$fieldName])
                )
            ) {
                // already defined and initialized
                continue;
            }

            $config = $this->relationConfigProvider->getRelationConfig(
                $mapping['targetEntity'],
                $version,
                $requestType,
                $extras
            );
            if (isset($definition[$fieldName]) && is_array($definition[$fieldName])) {
                $config = array_merge_recursive($config, [ConfigUtil::DEFINITION => $definition[$fieldName]]);
            }
            if ($this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)) {
                $config[ConfigUtil::DEFINITION][ConfigUtil::EXCLUDE] = true;
            }
            $definition[$fieldName] = $config;
        }

        return $definition;
    }
}
