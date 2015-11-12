<?php

namespace Oro\Bundle\ApiBundle\Processor\BuildConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class BuildDefinition implements ProcessorInterface
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
        /** @var BuildConfigContext $context */

        if ($context->hasResult()) {
            // a definition is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $fields       = array_fill_keys($metadata->getFieldNames(), null);
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $fieldName => $mapping) {
            $targetIdFields     = $this->doctrineHelper->getEntityIdentifierFieldNames($mapping['targetEntity']);
            $fields[$fieldName] = [
                'exclusion_policy' => 'all',
                'fields'           => count($targetIdFields) === 1
                    ? reset($targetIdFields)
                    : $targetIdFields
            ];
        }

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => $fields
        ];

        $context->setResult($config);
    }
}
