<?php

namespace Oro\Bundle\PlatformBundle\Configurator;

use Doctrine\ORM\Configuration;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;

/**
 * Changes Configuration->autoGenerateProxyClasses to true if application isn't installed
 */
class OrmConfigurationConfigurator
{
    public function __construct(private ApplicationState $applicationState)
    {
    }

    public function configure(Configuration $configuration): void
    {
        if (!$this->applicationState->isInstalled()) {
            $configuration->setAutoGenerateProxyClasses(true);
        }
    }
}
