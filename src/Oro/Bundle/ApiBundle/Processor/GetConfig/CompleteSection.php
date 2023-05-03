<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;

/**
 * The base class for processors that make sure that all supported filters and sorters
 * are added to API configuration and all of them are fully configured.
 */
abstract class CompleteSection implements ProcessorInterface
{
    protected DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    protected function complete(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        if (!$section->isExcludeAll()) {
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $this->completeFields($section, $entityClass, $definition);
            }
            $section->setExcludeAll();
        }

        $this->applyFieldExclusions($section, $definition);
    }

    abstract protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void;

    protected function applyFieldExclusions(EntityConfigInterface $section, EntityDefinitionConfig $definition): void
    {
        $fields = $section->getFields();
        foreach ($fields as $fieldName => $sectionField) {
            if (!$sectionField->hasExcluded()) {
                $field = $definition->getField($fieldName);
                if (null !== $field && $field->isExcluded()) {
                    $sectionField->setExcluded();
                }
            }
        }
    }
}
