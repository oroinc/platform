<?php
namespace Oro\Bundle\MessageQueueBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class RootJobActionConfiguration
{
    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getConfiguration(ResultRecordInterface $record)
    {
        return [
            'view' => true,
            'interrupt_root_job' => ! $record->getRootEntity()->isInterrupted(),
        ];
    }
}
