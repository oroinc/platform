<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class MergeMassActionListener
{
    /**
     * Remove useless fields in case of filtering
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $parameters = $event->getParameters();

        if (!empty($parameters['contactId'])) {
            $this->removeColumn($config, 'contactName');
        }

        if (!empty($parameters['accountId'])) {
            $this->removeColumn($config, 'accountName');
        }
    }

    /**
     * @param object $config
     * @param string $columnName
     */
    public function removeColumn($config, $columnName)
    {
        //@todo: implement
    }
}
