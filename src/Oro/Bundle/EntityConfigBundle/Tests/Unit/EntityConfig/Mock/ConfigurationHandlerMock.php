<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;

class ConfigurationHandlerMock extends ConfigurationHandler
{
    private static ConfigurationHandler $instance;

    /**
     * @return ConfigurationHandler
     */
    public static function getInstance(): ConfigurationHandler
    {
        if (!isset(self::$instance)) {
            self::$instance = new static([]);
        }

        return self::$instance;
    }

    public function validate(int $type, string $scope, array $values, string $entityOrTableName): void
    {
    }

    public function process(
        int $type,
        string $sectionName,
        array $values,
        string $entityOrTableName = null,
        string $fieldType = null,
        array $skipOptionsFilterRegex = []
    ): array {
        return $values;
    }
}
