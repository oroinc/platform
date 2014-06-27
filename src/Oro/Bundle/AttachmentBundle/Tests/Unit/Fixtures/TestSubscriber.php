<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
