<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Defines the contract for providers that supply global options to console commands.
 *
 * Implementations of this interface are responsible for adding global options to console commands
 * and resolving the values of those options from the command input. Global options are options
 * that should be available to all or most console commands in the application.
 */
interface GlobalOptionsProviderInterface
{
    public function addGlobalOptions(Command $command);

    public function resolveGlobalOptions(InputInterface $input);
}
