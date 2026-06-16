<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Event;

use Symfony\Component\Console\Event\ConsoleEvent;

/**
 * Dispatched by TransportConsumeMessagesCommand::initialize() after the input is bound and validated,
 * allowing listeners to safely read and modify input arguments and options.
 */
class TransportConsumeMessagesCommandConsoleEvent extends ConsoleEvent
{
}
