<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

abstract class CompleteSection implements ProcessorInterface
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
     * @param EntityConfigInterface  $section
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $definition
     */
    protected function complete(
        EntityConfigInterface $section,
        $entityClass,
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

    /**
     * @param EntityConfigInterface  $section
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $definition
     */
    abstract protected function completeFields(
        EntityConfigInterface $section,
        $entityClass,
        EntityDefinitionConfig $definition
    );

    /**
     * @param EntityConfigInterface  $section
     * @param EntityDefinitionConfig $definition
     */
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
