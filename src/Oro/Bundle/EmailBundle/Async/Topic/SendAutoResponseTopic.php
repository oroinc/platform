<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Send auto response for single email.
 */
class SendAutoResponseTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.send_auto_response';
    }

    public static function getDescription(): string
    {
        return 'Send auto response for single email';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'id'
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('id', ['string', 'int']);
    }
}
