<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\FieldConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class CompleteDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var FieldConfigProvider */
    protected $fieldConfigProvider;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     * @param ConfigProvider             $configProvider
     * @param FieldConfigProvider        $fieldConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider,
        ConfigProvider $configProvider,
        FieldConfigProvider $fieldConfigProvider
    ) {
        $this->doctrineHelper      = $doctrineHelper;
        $this->exclusionProvider   = $exclusionProvider;
        $this->configProvider      = $configProvider;
        $this->fieldConfigProvider = $fieldConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var array|null $definition */
        $definition = $context->getResult();
        if (empty($definition) || ConfigUtil::isExcludeAll($definition)) {
            // nothing to normalize
            return;
        }

        if (ConfigUtil::getArrayValue($definition, ConfigUtil::DEFINITION)) {
            $definition = ConfigUtil::getArrayValue($definition, ConfigUtil::DEFINITION);
        }

        $fields = ConfigUtil::getArrayValue($definition, ConfigUtil::FIELDS);

        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $fields = $this->completeDefinition(
                $fields,
                $entityClass,
                $context->getVersion(),
                $context->getRequestType(),
                $context->getExtras()
            );
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
     * @param string[] $requestType
     * @param array    $extras
     *
     * @return array
     */
    protected function completeDefinition(
        array $definition,
        $entityClass,
        $version,
        array $requestType,
        array $extras
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
     * @param string[]      $requestType
     * @param array         $extras
     *
     * @return array
     */
    protected function getFields(
        array $definition,
        ClassMetadata $metadata,
        $version,
        array $requestType,
        array $extras
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
     * @param string[]      $requestType
     * @param array         $extras
     *
     * @return array
     */
    protected function getAssociations(
        array $definition,
        ClassMetadata $metadata,
        $version,
        array $requestType,
        array $extras
    ) {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (!$this->isAssociationCompletionRequired($fieldName, $definition)) {
                continue;
            }

            $identifierFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass(
                $mapping['targetEntity']
            );

            $config = [
                ConfigUtil::DEFINITION => [
                    ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                    ConfigUtil::FIELDS           => count($identifierFieldNames) === 1
                        ? reset($identifierFieldNames)
                        : array_fill_keys($identifierFieldNames, null)
                ]
            ];

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

    /**
     * @param $fieldName
     * @param $definition
     *
     * @return bool|string
     */
    protected function isAssociationCompletionRequired($fieldName, $definition)
    {
        if (!array_key_exists($fieldName, $definition)) {
            return true;
        }

        if (!is_array($definition[$fieldName])) {
            return false;
        }

        if (isset($definition[$fieldName][ConfigUtil::DEFINITION])) {
            if (null === $definition[$fieldName][ConfigUtil::DEFINITION]) {
                return true;
            }
            if (is_array($definition[$fieldName][ConfigUtil::DEFINITION])) {
                return false === ConfigUtil::isRelationInitialized($definition[$fieldName][ConfigUtil::DEFINITION]);
            }
        }

        return false === ConfigUtil::isRelationInitialized($definition[$fieldName]);
    }
}
