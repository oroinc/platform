<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class FilterFieldsByExtra implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param EntityClassTransformerInterface $entityClassTransformer
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityClassTransformerInterface $entityClassTransformer)
    {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassTransformer = $entityClassTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var array|null $definition */
        $definition = $context->getResult();
        if (empty($definition) || !is_array($definition[ConfigUtil::FIELDS])) {
            // nothing to do
            return;
        }
        $fieldsDefinition = $definition[ConfigUtil::FIELDS];

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return;
        }

        $filterFieldsConfig = $context->get(FilterFieldsConfigExtra::NAME);

        $this->filterFields($entityClass, $fieldsDefinition, $filterFieldsConfig);
        $this->filterAssociations($entityClass, $fieldsDefinition, $filterFieldsConfig);

        $context->setResult(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                ConfigUtil::FIELDS           => $fieldsDefinition
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param array  $fieldsDefinition
     * @param array  $filterFieldsConfig
     */
    protected function filterFields($entityClass, &$fieldsDefinition, &$filterFieldsConfig)
    {
        $entityAlias           = $this->entityClassTransformer->transform($entityClass);
        $rootEntityIdentifiers = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (array_key_exists($entityAlias, $filterFieldsConfig)) {
            $allowedFields = $filterFieldsConfig[$entityAlias];
            foreach ($fieldsDefinition as $name => &$def) {
                if (isset($def[ConfigUtil::DEFINITION][ConfigUtil::FIELDS])
                    && array_key_exists($name, $filterFieldsConfig)
                ) {
                    continue;
                }

                if (!in_array($name, $allowedFields, true)
                    && !in_array($name, $rootEntityIdentifiers, true)
                    && !ConfigUtil::isMetadataProperty($name)
                ) {
                    $def[ConfigUtil::DEFINITION][ConfigUtil::EXCLUDE] = true;
                }
            }
            unset($filterFieldsConfig[$entityAlias]);
        }
    }

    /**
     * @param string $entityClass
     * @param array  $fieldsDefinition
     * @param array  $filterFieldsConfig
     */
    protected function filterAssociations($entityClass, &$fieldsDefinition, &$filterFieldsConfig)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $associationsMapping = $metadata->getAssociationMappings();
        foreach ($associationsMapping as $fieldName => $mapping) {
            $identifierFieldNames     = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass(
                $mapping['targetEntity']
            );

            if (!isset($filterFieldsConfig[$fieldName])
                || (
                    !isset($fieldsDefinition[$fieldName][ConfigUtil::DEFINITION][ConfigUtil::FIELDS])
                    || !is_array($fieldsDefinition[$fieldName][ConfigUtil::DEFINITION][ConfigUtil::FIELDS])
                )
            ) {
                continue;
            }

            $associationAllowedFields = $filterFieldsConfig[$fieldName];
            foreach ($fieldsDefinition[$fieldName][ConfigUtil::DEFINITION][ConfigUtil::FIELDS] as $name => &$def) {
                if (in_array($name, $identifierFieldNames, true)) {
                    continue;
                }

                if (!in_array($name, $associationAllowedFields, true) && !ConfigUtil::isMetadataProperty($name)) {
                    if (is_array($def)) {
                        $def = array_merge($def, [ConfigUtil::EXCLUDE => true]);
                    } else {
                        $def = [ConfigUtil::EXCLUDE => true];
                    }
                }
            }
        }
    }
}
