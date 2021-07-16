<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting as BaseSetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Sets default sorting for JSON:API requests.
 * The default sorting expression is "id ASC".
 */
class SetDefaultSorting extends BaseSetDefaultSorting
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue(EntityDefinitionConfig $config, ?SortersConfig $configOfSorters): array
    {
        $orderBy = $config->getOrderBy();
        if (!$orderBy && $this->isSorterByIdEnabled($config, $configOfSorters)) {
            $orderBy = [JsonApiDoc::ID => Criteria::ASC];
        }

        return $orderBy;
    }

    private function isSorterByIdEnabled(EntityDefinitionConfig $config, ?SortersConfig $configOfSorters): bool
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (!$idFieldNames) {
            return false;
        }

        $enabled = true;
        foreach ($idFieldNames as $fieldName) {
            $field = $config->getField($fieldName);
            if (null !== $field) {
                $fieldName = $field->getPropertyPath($fieldName);
            }
            if (!$this->isSorterEnabled($fieldName, $configOfSorters)) {
                $enabled = false;
                break;
            }
        }

        return $enabled;
    }
}
