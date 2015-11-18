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

class NormalizeDefinition implements ProcessorInterface
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

        $fields = ConfigUtil::getFields($definition);

        if (!ConfigUtil::isExcludeAll($definition)) {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntity($entityClass)) {
                $fields = $this->completeDefinition(
                    $fields,
                    $entityClass,
                    $context->getVersion(),
                    $context->getRequestType(),
                    $context->getConfigSections()
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
     * @param string[] $configSections
     *
     * @return array
     */
    protected function completeDefinition(
        array $definition,
        $entityClass,
        $version,
        $requestType,
        $configSections
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $definition = $this->getFields($definition, $metadata, $version, $requestType, $configSections);
        $definition = $this->getAssociations($definition, $metadata, $version, $requestType, $configSections);

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     * @param string        $requestType
     * @param string[]      $configSections
     *
     * @return array
     */
    protected function getFields(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $configSections
    ) {
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            if ($this->exclusionProvider->isIgnoredField($metadata, $fieldName)) {
                $config = [ConfigUtil::EXCLUDE => true];
            } else {
                $config = $this->fieldConfigProvider->getFieldConfig(
                    $metadata->name,
                    $fieldName,
                    $version,
                    $requestType,
                    $configSections
                );
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
     * @param string[]      $configSections
     *
     * @return array
     */
    protected function getAssociations(
        array $definition,
        ClassMetadata $metadata,
        $version,
        $requestType,
        $configSections
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

            $targetEntityClass = $mapping['targetEntity'];
            if ($this->exclusionProvider->isIgnoredEntity($targetEntityClass)
                || $this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)
            ) {
                $config = [ConfigUtil::EXCLUDE => true];
            } else {
                $config = $this->relationConfigProvider->getRelationConfig(
                    $targetEntityClass,
                    $version,
                    $requestType,
                    $configSections
                );
                if (isset($definition[$fieldName]) && is_array($definition[$fieldName])) {
                    $config = array_merge_recursive($config, [ConfigUtil::DEFINITION => $definition[$fieldName]]);
                }
            }
            $definition[$fieldName] = $config;
        }

        return $definition;
    }
}
