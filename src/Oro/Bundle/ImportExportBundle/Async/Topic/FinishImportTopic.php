<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic indicating the completion of the import.
 */
class FinishImportTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.importexport.finish_import_topic';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Sends an event that signals the completion of the import.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('rootImportJobId')
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define('processorAlias')
            ->required()
            ->allowedTypes('string');

        $resolver
            ->define('type')
            ->required()
            ->allowedTypes('string')
            ->allowedValues(ProcessorRegistry::TYPE_IMPORT, ProcessorRegistry::TYPE_IMPORT_VALIDATION);

        $resolver
            ->define('options')
            ->required()
            ->allowedTypes('array', 'null')
            ->normalize(fn (Options $options, $value) => $value ?? []);
    }
}
