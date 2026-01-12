<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * TextGuesser class
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class TextGuesser implements GuesserInterface
{
    public function __construct(
        protected DoctrineHelper $doctrineHelper,
        private FieldHelper $fieldHelper,
    ) {
    }

    #[\Override]
    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);

        $result = [];
        if (
            $isEnabledInline
            && $this->hasField($metadata, $entityName, $columnName)
            && !$metadata->hasAssociation($columnName)
        ) {
            $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
        }

        return $result;
    }

    private function hasField($metadata, $entityName, $columnName)
    {
        return $metadata->hasField($columnName)
            || $this->fieldHelper->getFieldConfig('enum', $entityName, $columnName);
    }
}
