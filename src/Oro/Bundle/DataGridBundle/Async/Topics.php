<?php
namespace Oro\Bundle\DataGridBundle\Async;

class Topics
{
    const EXPORT_CSV = 'oro.datagrid.export.csv';
    const EXPORT_XLSX = 'oro.datagrid.export.xlsx';

    /**
     * @param string $format
     *
     * @return string|null
     */
    public static function getTopicNameByExportFormat($format)
    {
        $topicName = sprintf("self::EXPORT_%s", strtoupper($format));

        return defined($topicName) ? constant($topicName) : null;
    }
}
