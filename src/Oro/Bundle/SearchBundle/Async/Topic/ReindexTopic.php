<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reindex search index.
 */
class ReindexTopic extends AbstractTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.search.reindex';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Reindex search index';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        $jobName = self::getName();
        if ($messageBody) {
            $jobName .= ':';
            if (\count($messageBody) === 1) {
                $jobName .= reset($messageBody);
            } else {
                $entityClasses = $messageBody;
                sort($entityClasses, SORT_STRING);
                $jobName .= hash('sha256', implode(',', $entityClasses));
            }
        }

        return $jobName;
    }
}
