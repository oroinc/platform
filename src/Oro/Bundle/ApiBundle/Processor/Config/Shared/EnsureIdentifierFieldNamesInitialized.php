<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Makes sure that identifier field names are set for ORM entities.
 */
class EnsureIdentifierFieldNamesInitialized implements ProcessorInterface
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
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (!empty($identifierFieldNames)) {
            // identifier field names are already set
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $identifierFieldNames = $this->doctrineHelper->getEntityMetadataForClass($entityClass)
            ->getIdentifierFieldNames();
        foreach ($identifierFieldNames as &$idFieldName) {
            $fieldName = $definition->findFieldNameByPropertyPath($idFieldName);
            if ($fieldName && $fieldName !== $idFieldName) {
                $idFieldName = $fieldName;
            }
        }
        unset($idFieldName);

        $definition->setIdentifierFieldNames($identifierFieldNames);
    }
}
