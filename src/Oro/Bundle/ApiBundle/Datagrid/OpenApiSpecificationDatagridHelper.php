<?php

namespace Oro\Bundle\ApiBundle\Datagrid;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Provides methods to configure OpenAPI specifications datagrid.
 */
class OpenApiSpecificationDatagridHelper
{
    /**
     * Configures actions' visibility for OpenAPI specifications datagrid.
     */
    public function getActionsVisibility(ResultRecordInterface $record, array $actions): array
    {
        $visibility = [];
        foreach ($actions as $action => $item) {
            if ('download' === $action) {
                $visibility[$action] = null !== $record->getValue('specificationCreatedAt');
            } elseif ('renew' === $action) {
                $visibility[$action] = OpenApiSpecification::STATUS_CREATING !== $record->getValue('status');
            } elseif ('publish' === $action) {
                $visibility[$action] =
                    !$record->getValue('published')
                    && null !== $record->getValue('specificationCreatedAt');
            } elseif ('update' === $action) {
                $visibility[$action] = !$record->getValue('published');
            } else {
                $visibility[$action] = true;
            }
        }

        return $visibility;
    }
}
