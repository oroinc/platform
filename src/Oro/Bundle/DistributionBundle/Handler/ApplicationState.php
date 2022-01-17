<?php

namespace Oro\Bundle\DistributionBundle\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Finds out if application was installed
 */
class ApplicationState
{
    private bool $installed = false;

    public function __construct(private Connection $connection)
    {
    }

    public function isInstalled(): bool
    {
        if (!$this->installed) {
            try {
                $this->installed = (bool)$this->connection->fetchOne(
                    "SELECT text_value FROM oro_config_value " .
                    "WHERE name = 'is_installed' AND section = 'oro_distribution'"
                );
            } catch (Exception $exception) {
                $this->installed = false;
            }
        }
        return $this->installed;
    }

    public function setInstalled(): bool
    {
        if (!$this->isInstalled()) {
            try {
                $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                $configId = $this->connection->fetchOne("SELECT id FROM oro_config WHERE entity = 'app'");
                $this->connection->insert(
                    'oro_config_value',
                    [
                        'config_id' => $configId,
                        'name' => 'is_installed',
                        'section' => 'oro_distribution',
                        'text_value' => 1,
                        'object_value' => 'Tjs=',
                        'array_value' => 'Tjs=',
                        'type' => 'scalar',
                        'created_at' => $date,
                        'updated_at' => $date
                    ],
                    [
                        'config_id' => 'integer',
                        'name' => 'string',
                        'section' => 'string',
                        'text_value' => 'string',
                        'object_value' => 'string',
                        'array_value' => 'string',
                        'type' => 'string',
                        'created_at' => 'string',
                        'updated_at' => 'string'
                    ]
                );
                $this->installed = true;
            } catch (Exception $exception) {
                $this->installed = false;
            }
        }

        return $this->installed;
    }
}
