<?php

namespace Oro\Bundle\CronBundle\Command;

/**
 * Cron command that should be executed as a background process without locking the main thread.
 */
interface SynchronousCommandInterface
{
}
