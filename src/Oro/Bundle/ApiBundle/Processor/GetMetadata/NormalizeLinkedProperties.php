<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Expands metadata of root entity adding fields which are aliases for child associations.
 * For example, if there is the field configuration like:
 * addressName:
 *      property_path: address.name
 * the "addressName" field should be added to the metadata.
 * The metadata of this field should be based on metadata of the "name" field of the "address" association.
 */
class NormalizeLinkedProperties implements ProcessorInterface
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

        if (!$context->hasResult()) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // a configuration does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $entityMetadata = $context->getResult();
        $this->normalizeMetadata($entityMetadata, $config);
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $definition
     */
    protected function normalizeMetadata(EntityMetadata $entityMetadata, EntityDefinitionConfig $definition)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$entityMetadata->hasProperty($fieldName) && $field->hasPropertyPath()) {
                $path = ConfigUtil::explodePropertyPath($field->getPropertyPath());
                if (count($path) > 1) {
                    $this->addLinkedProperty($entityMetadata, $fieldName, $path);
                }
            }
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param string         $propertyName
     * @param string[]       $propertyPath
     */
    protected function addLinkedProperty(EntityMetadata $entityMetadata, $propertyName, array $propertyPath)
    {
        $linkedProperty = array_pop($propertyPath);
        $classMetadata  = $this->doctrineHelper->findEntityMetadataByPath(
            $entityMetadata->getClassName(),
            $propertyPath
        );
        if (null !== $classMetadata) {
            if ($classMetadata->hasAssociation($linkedProperty)) {
                $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $associationMetadata->setName($propertyName);
                $entityMetadata->addAssociation($associationMetadata);
            } else {
                $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $fieldMetadata->setName($propertyName);
                $entityMetadata->addField($fieldMetadata);
            }
        }
    }
}
