<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Stub;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TopicStub extends AbstractTopic
{
    public static function getName(): string
    {
        return 'topic.stub';
    }

    public static function getDescription(): string
    {
        return 'Topic Stub Description';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setDefined('sample_key');
    }
}
