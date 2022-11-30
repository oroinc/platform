<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The chunk of email user ids for wich the recalculation of the visibility should be done.
 */
class RecalculateEmailVisibilityChunkTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.recalculate_email_visibility_chunk';
    }

    public static function getDescription(): string
    {
        return 'Recalculate the visibility of email users by given ids';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['jobId', 'ids'])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('ids', ['string[]', 'int[]']);
    }
}
