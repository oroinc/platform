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
        $topic = null;

        switch (strtolower($format)) {
            case 'csv':
                $topic = self::EXPORT_CSV;
                break;
            case 'xlsx':
                $topic = self::EXPORT_XLSX;
                break;
            default:
                break;
        }

        return $topic;
    }
}
