<?php

namespace Oro\Component\MessageQueue\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents an absence of topic.
 */
class NullTopic extends AbstractTopic
{
    public static function getName(): string
    {
        throw new \LogicException('Name is not supported for ' . __CLASS__);
    }

    public static function getDescription(): string
    {
        throw new \LogicException('Description is not supported for ' . __CLASS__);
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
