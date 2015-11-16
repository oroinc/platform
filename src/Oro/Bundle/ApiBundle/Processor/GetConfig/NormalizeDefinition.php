<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class NormalizeDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigBag */
    protected $configBag;

    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /**
     * @param DoctrineHelper                   $doctrineHelper
     * @param ConfigBag                        $configBag
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigBag $configBag,
        EntityHierarchyProviderInterface $entityHierarchyProvider
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->configBag               = $configBag;
        $this->entityHierarchyProvider = $entityHierarchyProvider;
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

        $fields = !empty($definition['fields'])
            ? $definition['fields']
            : [];

        if (!isset($definition['exclusion_policy']) || $definition['exclusion_policy'] !== 'all') {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntity($entityClass)) {
                $fields = $this->completeDefinition($fields, $entityClass, $context->getVersion());
            }
        }

        $context->setResult(
            [
                'exclusion_policy' => 'all',
                'fields'           => $fields
            ]
        );
    }

    /**
     * @param array  $definition
     * @param string $entityClass
     * @param string $version
     *
     * @return array
     */
    protected function completeDefinition(array $definition, $entityClass, $version)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $definition = $this->getFields($definition, $metadata);
        $definition = $this->getAssociations($definition, $metadata, $version);

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getFields(array $definition, ClassMetadata $metadata)
    {
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $definition[$fieldName] = null;
        }

        return $definition;
    }

    /**
     * @param array         $definition
     * @param ClassMetadata $metadata
     * @param string        $version
     *
     * @return array
     */
    protected function getAssociations(array $definition, ClassMetadata $metadata, $version)
    {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $definition[$fieldName] = $this->getAssociationConfig($mapping, $version);
        }

        return $definition;
    }

    /**
     * @param array  $mapping
     * @param string $version
     *
     * @return array
     */
    protected function getAssociationConfig($mapping, $version)
    {
        $targetEntityClass = $mapping['targetEntity'];

        $config = $this->configBag->getRelationConfig($targetEntityClass, $version);

        if (null === $config || $this->isInherit($config)) {
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($targetEntityClass);
            foreach ($parentClasses as $parentClass) {
                $parentConfig = $this->configBag->getRelationConfig($parentClass, $version);
                if (!empty($parentConfig)) {
                    if (null === $config) {
                        $config = $parentConfig;
                    } else {
                        $config = array_merge_recursive($parentConfig, $config);
                    }
                    if (!$this->isInherit($parentConfig)) {
                        break;
                    }
                }
            }
        }

        if (empty($config)) {
            $targetIdFields = $this->doctrineHelper->getEntityIdentifierFieldNames($targetEntityClass);
            $config         = [
                'exclusion_policy' => 'all',
                'fields'           => count($targetIdFields) === 1
                    ? reset($targetIdFields)
                    : $targetIdFields
            ];
        }

        return $config;
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    protected function isInherit($config)
    {
        return !isset($config['inherit']) || $config['inherit'];
    }
}
