<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

interface GlobalOptionsProviderInterface
{
    /**
     * @param Command $command
     */
    public function addGlobalOptions(Command $command);

    /**
     * @param InputInterface $input
     */
    public function resolveGlobalOptions(InputInterface $input);
}
