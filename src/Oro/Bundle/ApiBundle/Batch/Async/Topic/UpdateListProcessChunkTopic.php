<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to process a chunk of data of API batch update request.
 */
class UpdateListProcessChunkTopic extends AbstractUpdateListTopic
{
    public static function getName(): string
    {
        return 'oro.api.update_list.process_chunk';
    }

    public static function getDescription(): string
    {
        return 'Processes a chunk of data of API batch update request.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('jobId')
            ->setAllowedTypes('jobId', 'int');

        $resolver
            ->setRequired('fileName')
            ->setAllowedTypes('fileName', 'string');

        $resolver
            ->setRequired('fileIndex')
            ->setAllowedTypes('fileIndex', 'int');

        $resolver
            ->setRequired('firstRecordOffset')
            ->setAllowedTypes('firstRecordOffset', 'int');

        $resolver
            ->setRequired('sectionName')
            ->setAllowedTypes('sectionName', 'string');

        $resolver
            ->setDefined('extra_chunk')
            ->setDefault('extra_chunk', false)
            ->setAllowedTypes('extra_chunk', 'bool');
    }
}
