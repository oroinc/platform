<?php

namespace Oro\Bundle\PlatformBundle\Configurator;

use Doctrine\ORM\Configuration;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\CommandExecutor;

/**
 * Changes Configuration->autoGenerateProxyClasses to true if application isn't installed
 */
class OrmConfigurationConfigurator
{
    public function __construct(private ApplicationState $applicationState, private string $env)
    {
    }

    public function configure(Configuration $configuration): void
    {
        if (
            !$this->applicationState->isInstalled()
            || (CommandExecutor::isCurrentCommand('oro:message-queue:consume') && $this->env === 'test')
        ) {
            $configuration->setAutoGenerateProxyClasses(true);
        }
    }
}
