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

    #[\Override]
    protected function getAllowedSortFieldsDescription(
        EntityDefinitionConfig $config,
        SortersConfig $configOfSorters
    ): ?string {
        $fieldNames = [];
        if ($this->isSorterByIdEnabled($config, $configOfSorters)) {
            $fieldNames[] = JsonApiDoc::ID;
        }
        $idFieldNames = $config->getIdentifierFieldNames();
        foreach ($configOfSorters->getFields() as $fieldName => $field) {
            if (!$field->isExcluded() && !\in_array($fieldName, $idFieldNames, true)) {
                $fieldNames[] = $fieldName;
            }
        }
        if (!$fieldNames) {
            return null;
        }

        if (\count($fieldNames) > 1) {
            sort($fieldNames);
        }

        return 'Allowed fields: ' . implode(', ', $fieldNames) . '.';
    }

    private function isSorterByIdEnabled(EntityDefinitionConfig $config, ?SortersConfig $configOfSorters): bool
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (!$idFieldNames) {
            return false;
        }

        $enabled = true;
        foreach ($idFieldNames as $fieldName) {
            if (!$this->isSorterEnabled($fieldName, $configOfSorters)) {
                $enabled = false;
                break;
            }
        }

        return $enabled;
    }
}
