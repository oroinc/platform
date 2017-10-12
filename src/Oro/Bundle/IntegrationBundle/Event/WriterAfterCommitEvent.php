<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WriterAfterCommitEvent extends Event
{
    const NAME = 'oro_integration.writer_after_commit';
}
