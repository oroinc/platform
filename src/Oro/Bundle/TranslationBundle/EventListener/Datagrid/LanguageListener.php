<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class LanguageListener
{
    const DATA_NAME = 'translationCompleteness';

    /** @var TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /**
     * @param TranslationStatisticProvider $translationStatisticProvider
     */
    public function __construct(TranslationStatisticProvider $translationStatisticProvider)
    {
        $this->translationStatisticProvider = $translationStatisticProvider;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $stats = $this->getStatistic();

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $code = $record->getValue('code');

            $record->setValue(self::DATA_NAME, isset($stats[$code]) ? (int)$stats[$code] : 0);
        }
    }

    /**
     * @return array
     */
    protected function getStatistic()
    {
        $stats = $this->translationStatisticProvider->get();
        $result = [];

        foreach ($stats as $stat) {
            $result[$stat['code']] = $stat['translationStatus'];
        }

        return $result;
    }
}
