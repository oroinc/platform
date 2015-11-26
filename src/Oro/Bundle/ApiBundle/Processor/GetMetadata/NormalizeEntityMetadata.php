<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeEntityMetadata implements ProcessorInterface
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
        if (empty($config) || empty($config[ConfigUtil::DEFINITION])) {
            // a configuration does not exist
            return;
        }

        /** @var EntityMetadata $entityMetadata */
        $entityMetadata = $context->getResult();
        $this->normalizeMetadata($entityMetadata, $config[ConfigUtil::DEFINITION]);
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param array          $config
     */
    protected function normalizeMetadata(EntityMetadata $entityMetadata, array $config)
    {
        $fields = ConfigUtil::getArrayValue($config, ConfigUtil::FIELDS);
        foreach ($fields as $fieldName => $fieldConfig) {
            if (null !== $fieldConfig && isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                $path = ConfigUtil::explodePropertyPath($fieldConfig[ConfigUtil::PROPERTY_PATH]);
                if (count($path) > 1) {
                    $this->addLinkedProperty($entityMetadata, $fieldName, $path);
                }
            }
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param string         $name
     * @param string[]       $path
     */
    protected function addLinkedProperty(EntityMetadata $entityMetadata, $name, array $path)
    {
        $linkedProperty = array_pop($path);
        $classMetadata  = $this->doctrineHelper->findEntityMetadataByPath($entityMetadata->getClassName(), $path);
        if (null !== $classMetadata) {
            if ($classMetadata->hasAssociation($linkedProperty)) {
                $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $associationMetadata->setName($name);
                $entityMetadata->addAssociation($associationMetadata);
            } else {
                $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $fieldMetadata->setName($name);
                $entityMetadata->addField($fieldMetadata);
            }
        }
    }
}
