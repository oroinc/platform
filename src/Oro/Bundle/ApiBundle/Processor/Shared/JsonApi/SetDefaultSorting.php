<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting as BaseSetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Sets default sorting for JSON API requests.
 * The default sorting expression is "id ASC".
 */
class SetDefaultSorting extends BaseSetDefaultSorting
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue(EntityDefinitionConfig $config): array
    {
        $orderBy = $config->getOrderBy();
        if (empty($orderBy)) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                $orderBy = [JsonApiDoc::ID => Criteria::ASC];
            }
        }

        return $orderBy;
    }
}
