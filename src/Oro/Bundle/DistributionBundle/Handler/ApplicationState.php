<?php

namespace Oro\Bundle\DistributionBundle\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;

/**
 * This service is used to find out whether the application was installed or not.
 */
class ApplicationState
{
    private bool $installed = false;

    public function __construct(
        private Connection $connection
    ) {
    }

    public function isInstalled(): bool
    {
        if (!$this->installed) {
            try {
                $this->installed = (bool)$this->connection->fetchOne(
                    'SELECT text_value FROM oro_config_value WHERE name = ? AND section = ?',
                    ['is_installed', 'oro_distribution'],
                    [Types::STRING, Types::STRING]
                );
            } catch (Exception $e) {
                $this->installed = false;
            }
        }

        return $this->installed;
    }

    public function setInstalled(): bool
    {
        if (!$this->isInstalled()) {
            $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $nullValue = base64_encode(serialize(null));
            try {
                $configId = $this->connection->fetchOne(
                    'SELECT id FROM oro_config WHERE entity = ?',
                    ['app'],
                    [Types::STRING]
                );
                $this->connection->insert(
                    'oro_config_value',
                    [
                        'config_id' => $configId,
                        'name' => 'is_installed',
                        'section' => 'oro_distribution',
                        'text_value' => '1',
                        'object_value' => $nullValue,
                        'array_value' => $nullValue,
                        'type' => 'scalar',
                        'created_at' => $date,
                        'updated_at' => $date
                    ],
                    [
                        'config_id' => Types::INTEGER,
                        'name' => Types::STRING,
                        'section' => Types::STRING,
                        'text_value' => Types::STRING,
                        'object_value' => Types::STRING,
                        'array_value' => Types::STRING,
                        'type' => Types::STRING,
                        'created_at' => Types::STRING,
                        'updated_at' => Types::STRING
                    ]
                );
                $this->installed = true;
            } catch (Exception $e) {
                $this->installed = false;
            }
        }

        return $this->installed;
    }
}
