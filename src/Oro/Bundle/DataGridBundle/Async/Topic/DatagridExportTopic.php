<?php

namespace Oro\Bundle\DataGridBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to export datagrid.
 */
class DatagridExportTopic extends DatagridPreExportTopic
{
    public static function getName(): string
    {
        return 'oro.datagrid.export';
    }

    public static function getDescription(): string
    {
        return 'Exports data from datagrid';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setDefined([
                'jobId',
                'exportType',
                'batchSize',
                'entity',
                'jobName',
                'outputFormat',
            ])
            ->setRequired([
                'jobId',
                'entity',
                'jobName',
                'outputFormat',
            ])
            ->setDefaults([
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'batchSize' => null,
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('exportType', 'string')
            ->addAllowedTypes('batchSize', ['int', 'null'])
            ->addAllowedTypes('entity', 'string')
            ->addAllowedTypes('jobName', 'string')
            ->addAllowedTypes('outputFormat', 'string');
    }
}
