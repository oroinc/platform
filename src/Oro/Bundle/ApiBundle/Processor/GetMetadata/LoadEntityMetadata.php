<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds metadata for manageable entity and its fields.
 */
class LoadEntityMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityMetadataFactory $entityMetadataFactory)
    {
        $this->doctrineHelper        = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata is already loaded
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $allowedFields = $this->getAllowedFields($context->getConfig());

        $classMetadata  = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->entityMetadataFactory->createEntityMetadata($classMetadata);

        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $fieldName) {
            if (!isset($allowedFields[$fieldName])) {
                continue;
            }
            $entityMetadata->addField(
                $this->entityMetadataFactory->createFieldMetadata($classMetadata, $fieldName)
            );
        }

        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $associationName) {
            if (!isset($allowedFields[$associationName])) {
                continue;
            }
            $entityMetadata->addAssociation(
                $this->entityMetadataFactory->createAssociationMetadata($classMetadata, $associationName)
            );
        }

        $context->setResult($entityMetadata);
    }

    /**
     * @param array|null $config
     *
     * @return array
     */
    protected function getAllowedFields($config)
    {
        $fields = [];
        if (!empty($config[ConfigUtil::FIELDS])) {
            if (is_array($config[ConfigUtil::FIELDS])) {
                foreach ($config[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                    if (!is_array($fieldConfig) || !ConfigUtil::isExclude($fieldConfig)) {
                        $propertyPath          = ConfigUtil::getPropertyPath($fieldConfig, $fieldName);
                        $fields[$propertyPath] = $fieldName;
                    }
                }
            } elseif (is_string($config[ConfigUtil::FIELDS])) {
                $fields[$config[ConfigUtil::FIELDS]] = $config[ConfigUtil::FIELDS];
            }
        }

        return $fields;
    }
}
