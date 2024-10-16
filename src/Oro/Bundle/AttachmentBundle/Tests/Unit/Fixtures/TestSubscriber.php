<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
