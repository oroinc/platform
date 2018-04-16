<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting as BaseSetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Sets default sorting for JSON API requests.
 * The sort filter name is "sort", the default sorting expression is "id ASC".
 */
class SetDefaultSorting extends BaseSetDefaultSorting
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue(EntityDefinitionConfig $config): array
    {
        return [JsonApiDoc::ID => Criteria::ASC];
    }
}
