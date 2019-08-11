<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds "title" meta property value to each result item if it was requested.
 */
class LoadTitleMetaPropertyForCollection extends LoadTitleMetaProperty
{
    /**
     * {@inheritdoc}
     */
    protected function updateData(array $data, string $entityClass, EntityDefinitionConfig $config): array
    {
        return $this->addTitles($data, $entityClass, $config);
    }
}
