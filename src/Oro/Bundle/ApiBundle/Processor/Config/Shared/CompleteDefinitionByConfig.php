<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\FieldConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class CompleteDefinitionByConfig extends CompleteDefinition
{
    /** @var RelationConfigProvider */
    protected $relationConfigProvider;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     * @param ConfigProvider             $configProvider
     * @param FieldConfigProvider        $fieldConfigProvider
     * @param RelationConfigProvider     $relationConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider,
        ConfigProvider $configProvider,
        FieldConfigProvider $fieldConfigProvider,
        RelationConfigProvider $relationConfigProvider
    ) {
        parent::__construct($doctrineHelper, $exclusionProvider, $configProvider, $fieldConfigProvider);

        $this->relationConfigProvider = $relationConfigProvider;
    }

    /**
     * {@inheritdoc}
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
