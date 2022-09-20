<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for sending different types of notifications related to import process.
 */
class SendImportNotificationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.importexport.send_import_notification';
    }

    public static function getDescription(): string
    {
        return 'Sends different types of notifications related to import process';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'rootImportJobId',
                'process',
                'userId',
                'originFileName'
            ])
            ->setRequired([
                'rootImportJobId',
                'process',
                'userId',
                'originFileName',
            ])
            ->addAllowedTypes('rootImportJobId', 'int')
            ->addAllowedTypes('process', 'string')
            ->addAllowedTypes('userId', 'int')
            ->addAllowedTypes('originFileName', 'string');
    }
}
