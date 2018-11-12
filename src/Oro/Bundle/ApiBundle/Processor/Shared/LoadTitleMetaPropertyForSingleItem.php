<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds "title" meta property value to a result item if it was requested.
 */
class LoadTitleMetaPropertyForSingleItem extends LoadTitleMetaProperty
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
        $data = $this->addTitles([$data], $entityClass, $config, $titleFieldName);

        return reset($data);
    }
}
