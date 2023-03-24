<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;

/**
 * Topic for splitting import process into a set of independent jobs.
 */
class PreImportTopic extends AbstractImportTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro.importexport.pre_import';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Splits import process into a set of independent jobs';
    }

    public function createJobName($messageBody): string
    {
        return sprintf(
            'oro:%s:%s:%s:%s:%d',
            $messageBody['process'],
            $messageBody['processorAlias'],
            $messageBody['jobName'],
            $messageBody['userId'],
            random_int(1, PHP_INT_MAX)
        );
    }
}
