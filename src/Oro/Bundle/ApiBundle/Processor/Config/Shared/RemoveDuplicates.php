<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

abstract class RemoveDuplicates implements ProcessorInterface
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
     * @param EntityConfigInterface $section
     * @param string                $entityClass
     */
    protected function removeDuplicatedFields(EntityConfigInterface $section, $entityClass)
    {
        if (!$section->hasFields()) {
            // nothing to remove
            return;
        }
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $fieldKeys = array_keys($section->getFields());
        foreach ($fieldKeys as $fieldKey) {
            $field = $section->getField($fieldKey);
            $path  = ConfigUtil::explodePropertyPath($field->getPropertyPath() ?: $fieldKey);
            if (count($path) === 1) {
                continue;
            }

            $fieldName = array_pop($path);
            if ($section->hasField(implode(ConfigUtil::PATH_DELIMITER, $path))) {
                $metadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);
                if (null !== $metadata && in_array($fieldName, $metadata->getIdentifierFieldNames(), true)) {
                    $section->removeField($fieldKey);
                }
            }
        }
    }
}
