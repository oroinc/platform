<?php

namespace Oro\Component\MessageQueue\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Interface for message queue topic.
 */
interface TopicInterface
{
    /**
     * @return string Topic name, e.g. 'oro.message_queue.my_topic'.
     */
    public static function getName(): string;

    /**
     * @return string Human-readable topic description.
     */
    public static function getDescription(): string;

    /**
     * @param string $queueName
     *
     * @return string Default priority for message of this topic in queue $queueName.
     */
    public function getDefaultPriority(string $queueName): string;

    /**
     * Configures {@see OptionsResolver} for a message body.
     * Used for validating body of a message before it is sent to message queue.
     *
     * @param OptionsResolver $resolver
     */
    public function configureMessageBody(OptionsResolver $resolver): void;
}
