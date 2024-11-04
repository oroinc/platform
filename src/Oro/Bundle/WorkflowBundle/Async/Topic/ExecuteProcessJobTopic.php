<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Async\Topic;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to execute workflow {@see ProcessJob}.
 */
class ExecuteProcessJobTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.workflow.execute_process_job';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Execute workflow process job.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('process_job_id')
            ->setAllowedTypes('process_job_id', 'int');
    }
}
