<?php

namespace Oro\Bundle\NotificationBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class StatusFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
            MassNotification::STATUS_FAILED  => $this->translator
                ->trans('oro.notification.massnotification.status.failed'),
            MassNotification::STATUS_SUCCESS => $this->translator
                ->trans('oro.notification.massnotification.status.success')
        ];
    }
}
