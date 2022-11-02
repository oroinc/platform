<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

interface GlobalOptionsProviderInterface
{
    public function addGlobalOptions(Command $command);

    public function resolveGlobalOptions(InputInterface $input);
}
