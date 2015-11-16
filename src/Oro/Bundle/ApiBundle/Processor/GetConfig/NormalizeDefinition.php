<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

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
                $fields = $this->completeDefinition($fields, $entityClass);
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
     *
     * @return array
     */
    protected function completeDefinition(array $definition, $entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $definition = $this->getFields($definition, $metadata);
        $definition = $this->getAssociations($definition, $metadata);

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
     *
     * @return array
     */
    protected function getAssociations(array $definition, ClassMetadata $metadata)
    {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            if (array_key_exists($fieldName, $definition)) {
                // already defined
                continue;
            }

            $targetIdFields         = $this->doctrineHelper->getEntityIdentifierFieldNames($mapping['targetEntity']);
            $definition[$fieldName] = [
                'exclusion_policy' => 'all',
                'fields'           => count($targetIdFields) === 1
                    ? reset($targetIdFields)
                    : $targetIdFields
            ];
        }

        return $definition;
    }
}
