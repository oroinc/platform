<?php

declare(strict_types=1);

namespace Oro\Bundle\RedisConfigBundle\Provider;

use Oro\Bundle\InstallerBundle\Provider\AbstractRequirementsProvider;
use Predis\Client;
use Predis\Connection\Aggregate\ReplicationInterface;
use Predis\PredisException;
use Symfony\Requirements\RequirementCollection;

/**
 * Requirements provider for redis
 */
class RedisRequirementsProvider extends AbstractRequirementsProvider
{
    public const REQUIRED_VERSION = '5.0';

    /**
     * @var Client[]|null[]
     */
    protected array $clients;

    public function __construct(array $clients)
    {
        $this->clients = $clients;
    }

    /**
     * @inheritDoc
     */
    public function getOroRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        $this->addVersionAndConfigurationRequirements($collection);

        return $collection;
    }

    protected function addVersionAndConfigurationRequirements(RequirementCollection $collection): void
    {
        foreach (array_filter($this->clients) as $id => $client) {
            if ($client->getConnection() instanceof ReplicationInterface) {
                $client->connect();

                $currentConnection = $client->getConnection()->getCurrent();
                $client->getConnection()->switchToMaster();
            }

            $isConnected = $client->isConnected();
            $errorMessage = '';
            if (!$isConnected) {
                try {
                    $client->connect();
                    $isConnected = $client->isConnected();
                    $client->disconnect();
                } catch (PredisException $exception) {
                    $errorMessage = $exception->getMessage();
                }
            }

            if ($isConnected) {
                $serverInfo = $client->info('Server');
                $version = $serverInfo['Server']['redis_version'];

                $collection->addRequirement(
                    version_compare($version, self::REQUIRED_VERSION, '>='),
                    'Connection for "' . $id . '" service has required Redis version (' . $version . ')',
                    'Redis version of connection for "'.$id.'" service must be '.self::REQUIRED_VERSION.' or higher'
                );
            } else {
                $collection->addRequirement(
                    false,
                    'Connection for "' . $id . '" service has invalid configuration.',
                    'Connection for "' . $id . '" service has invalid configuration. '. $errorMessage
                );
            }

            if ($client->getConnection() instanceof ReplicationInterface) {
                $client->getConnection()->switchTo($currentConnection);
            }
        }
    }
}
