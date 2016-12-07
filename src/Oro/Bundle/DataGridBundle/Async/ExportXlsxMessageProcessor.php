<?php
namespace Oro\Bundle\DataGridBundle\Async;

class ExportXlsxMessageProcessor extends AbstractExportMessageProcessor
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT_XLSX];
    }
}
