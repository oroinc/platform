<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Delegates adding and resolving global options for CLI commands to child providers.
 */
class GlobalOptionsProviderRegistry implements GlobalOptionsProviderInterface
{
    /** @var iterable|GlobalOptionsProviderInterface[] */
    private $providers;

    /**
     * @param iterable|GlobalOptionsProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalOptions(Command $command)
    {
        foreach ($this->providers as $provider) {
            $provider->addGlobalOptions($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveGlobalOptions(InputInterface $input)
    {
        foreach ($this->providers as $provider) {
            $provider->resolveGlobalOptions($input);
        }
    }
}
