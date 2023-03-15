<?php

namespace Oro\Bundle\EntityConfigBundle\Cache;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityExtendBundle\Migration\Query\CleanupEntityConfigQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Removes unused entity config options
 */
class CleanupUnusedConfigurationsCacheWarmer implements CacheWarmerInterface
{
    private ManagerRegistry $managerRegistry;

    private LoggerInterface $logger;

    private ApplicationState $appState;

    public function __construct(
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        ApplicationState $appState
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(string $cacheDir)
    {
        if (!$this->appState->isInstalled()) {
            return;
        }

        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');

        $query = new CleanupEntityConfigQuery(
            $this->getUnusedEntityConfigurations(),
            $this->getUnusedFieldConfigurations()
        );
        $query->setConnection($configConnection);
        $query->execute($this->logger);
    }

    /**
     * @return array
     */
    private function getUnusedEntityConfigurations(): array
    {
        return [
            'extend' => ['origin', 'extend_class'],
            'entity' => ['totals_mapping']
        ];
    }

    /**
     * @return array
     */
    private function getUnusedFieldConfigurations(): array
    {
        return [
            'extend' => ['origin']
        ];
    }
}
