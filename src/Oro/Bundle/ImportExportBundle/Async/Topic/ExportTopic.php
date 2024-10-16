<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for getting export result.
 */
class ExportTopic extends PreExportTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.importexport.export';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Gets export result';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setDefined([
                'jobId',
                'entity'
            ])
            ->setRequired([
                'jobId',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('entity', ['string', 'null']);
    }
}
