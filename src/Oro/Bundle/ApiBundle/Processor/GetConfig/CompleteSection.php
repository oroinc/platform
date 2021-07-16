<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that make sure that all supported filters and sorters
 * are added to API configuration and all of them are fully configured.
 */
abstract class CompleteSection implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    protected function complete(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ) {
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
    );

    protected function applyFieldExclusions(EntityConfigInterface $section, EntityDefinitionConfig $definition)
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
