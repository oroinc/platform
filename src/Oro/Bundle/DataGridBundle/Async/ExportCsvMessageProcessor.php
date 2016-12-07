<?php
namespace Oro\Bundle\DataGridBundle\Async;

class ExportCsvMessageProcessor extends AbstractExportMessageProcessor
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT_CSV];
    }
}
