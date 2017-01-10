<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * If it was requested, adds "title" meta property value to each result item.
 */
class LoadTitleMetaPropertyForCollection extends LoadTitleMetaProperty
{
    /**
     * {@inheritdoc}
     */
    protected function updateData(
        array $data,
        $entityClass,
        EntityDefinitionConfig $config,
        $titleFieldName
    ) {
        return $this->addTitles($data, $entityClass, $config, $titleFieldName);
    }
}
