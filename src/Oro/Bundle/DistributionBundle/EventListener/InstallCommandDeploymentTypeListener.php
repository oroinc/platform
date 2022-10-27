<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use LogicException;
use Oro\Bundle\DistributionBundle\Resolver\DeploymentConfigResolver;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Listener to show the warning in case the development type parameter is specified,
 * but the config file is not defined for it.
 */
class InstallCommandDeploymentTypeListener
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string|null
     */
    private $deploymentType;

    public function __construct($projectDir, $deploymentType)
    {
        $this->projectDir = $projectDir;
        $this->deploymentType = $deploymentType;
    }

    public function afterDatabasePreparation(InstallerEvent $event)
    {
        if (!$this->deploymentType) {
            return;
        }
        try {
            DeploymentConfigResolver::resolveConfig($this->projectDir);
        } catch (LogicException $e) {
            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->warning(sprintf($e->getMessage(), $this->deploymentType));
        }
    }
}
