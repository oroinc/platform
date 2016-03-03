<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;

abstract class RemoveExclusions implements ProcessorInterface
{
    /**
     * @param EntityConfigInterface $section
     */
    protected function removeExcludedFields(EntityConfigInterface $section)
    {
        if ($section->hasFields()) {
            $fieldNames = array_keys($section->getFields());
            foreach ($fieldNames as $fieldName) {
                if ($section->getField($fieldName)->isExcluded()) {
                    $section->removeField($fieldName);
                }
            }
        }
    }
}
