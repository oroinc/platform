<?php

namespace Oro\Bundle\DatagridBundle\Extension\Link;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class DatagridEmptyLinkRemoverExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetExists('actions') && array_key_exists('view', $config->offsetGet('actions'));
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = $result->getData();
        // forbid actions if row does not contain the report source entity id
        foreach ($rows as $key => $row) {
            if (!array_key_exists('id', $row)) {
                continue;
            }
            if (is_null($row['id'])) {
                $row['action_configuration']['view'] = false;
                $row['action_configuration']['update'] = false;
                $row['action_configuration']['delete'] = false;
            }
            $rows[$key] = $row;
        }

        $result->setData($rows);
    }
}
