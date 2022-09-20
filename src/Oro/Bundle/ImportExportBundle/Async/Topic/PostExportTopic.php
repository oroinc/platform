<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for batched export process finalization.
 */
class PostExportTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.importexport.post_export';
    }

    public static function getDescription(): string
    {
        return 'Finalizes batched export process';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'jobId',
                'jobName',
                'exportType',
                'outputFormat',
                'recipientUserId',
                'entity',
                'notificationTemplate',
            ])
            ->setRequired([
                'jobId',
                'jobName',
                'exportType',
                'outputFormat',
                'recipientUserId',
                'entity',
            ])
            ->setDefault('notificationTemplate', ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT)
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('jobName', 'string')
            ->addAllowedTypes('exportType', 'string')
            ->addAllowedTypes('outputFormat', 'string')
            ->addAllowedTypes('recipientUserId', 'int')
            ->addAllowedTypes('entity', 'string')
            ->addAllowedTypes('notificationTemplate', ['string', 'null']);
    }
}
