<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generating a list of records for export which are later used in child job.
 */
class PreExportTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.importexport.pre_export';
    }

    public static function getDescription(): string
    {
        return 'Generates a list of records for export which are later used in child job';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'jobName',
                'processorAlias',
                'outputFormat',
                'organizationId',
                'exportType',
                'options',
                'outputFilePrefix',
                'userId',
            ])
            ->setRequired([
                'jobName',
                'processorAlias',
            ])
            ->setDefaults([
                'outputFormat' => 'csv',
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'options' => [],
                'outputFilePrefix' => null,
            ])
            ->addAllowedTypes('jobName', 'string')
            ->addAllowedTypes('processorAlias', 'string')
            ->addAllowedTypes('outputFormat', 'string')
            ->addAllowedTypes('exportType', 'string')
            ->addAllowedTypes('organizationId', ['int', 'null'])
            ->addAllowedTypes('options', 'array')
            ->addAllowedTypes('outputFilePrefix', ['string', 'null'])
            ->addAllowedTypes('userId', 'int');
    }
}
