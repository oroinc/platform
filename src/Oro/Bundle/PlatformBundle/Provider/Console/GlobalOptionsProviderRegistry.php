<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GlobalOptionsProviderRegistry implements GlobalOptionsProviderInterface
{
    /**
     * @var array|GlobalOptionsProviderInterface[]
     */
    private $globalOptionsProviders = [];

    /**
     * @param GlobalOptionsProviderInterface $globalOptionsProvider
     */
    public function registerProvider(GlobalOptionsProviderInterface $globalOptionsProvider)
    {
        $this->globalOptionsProviders[] = $globalOptionsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalOptions(Command $command)
    {
        foreach ($this->globalOptionsProviders as $globalOptionsProvider) {
            $globalOptionsProvider->addGlobalOptions($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveGlobalOptions(InputInterface $input)
    {
        foreach ($this->globalOptionsProviders as $globalOptionsProvider) {
            $globalOptionsProvider->resolveGlobalOptions($input);
        }
    }
}
