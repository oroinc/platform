<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * Loads metadata for not ORM entity.
 */
class LoadObjectMetadata implements ProcessorInterface
{
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

        $config = $context->getConfig();
        if (!$config || !$config->hasFields()) {
            // a config does not exist or empty
            return;
        }

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($context->getClassName());
        $entityMetadata->setIdentifierFieldNames($config->getIdentifierFieldNames());
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $dataType = $field->getDataType();
            if (!$dataType) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if (!$targetClass) {
                $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
                $fieldMetadata->setDataType($dataType);
                $fieldMetadata->setIsNullable(
                    !in_array($fieldName, $entityMetadata->getIdentifierFieldNames(), true)
                );
            } else {
                $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
                $associationMetadata->setDataType($dataType);
                $associationMetadata->setIsCollection($field->isCollectionValuedTarget());
                $associationMetadata->setTargetClassName($targetClass);
                $associationMetadata->addAcceptableTargetClassName($targetClass);
            }
        }

        $context->setResult($entityMetadata);
    }
}
