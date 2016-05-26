<?php

namespace Oro\Bundle\NotificationBundle\Formatter;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class StatusFormatter
{
    /**
     * @param $gridName
     * @param $keyName
     * @param $node
     *
     * @return \Closure
     */
    public function format($gridName, $keyName, $node)
    {
        $labels = $this->getStatusLabels();
        return function (ResultRecordInterface $record) use ($labels) {
            $status = $record->getValue('status');
            $status = isset($labels[$status]) ? $labels[$status] : $status;

            return $status;
        };
    }

    /**
     * @return array
     */
    public function getStatusLabels()
    {
        return [
            MassNotification::STATUS_FAILED  => 'Failed',
            MassNotification::STATUS_SUCCESS => 'Success'
        ];
    }
}
