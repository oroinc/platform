<?php

namespace Oro\Bundle\MessageQueueBundle\Compatibility;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Compatibility Interface for message queue topic presented in 5.0.
 */
interface TopicInterface
{
    /**
     * @return string Topic name, e.g. 'oro.message_queue.my_topic'.
     */
    public static function getName(): string;

    /**
     * Configures {@see OptionsResolver} for a message body.
     * Used for validating body of a message before it is sent to message queue.
     *
     * @param OptionsResolver $resolver
     */
    public function configureMessageBody(OptionsResolver $resolver): void;
}
