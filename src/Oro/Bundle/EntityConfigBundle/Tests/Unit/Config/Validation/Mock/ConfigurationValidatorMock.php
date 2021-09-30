<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock;

use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationManager;
use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationValidator;

class ConfigurationValidatorMock extends ConfigurationValidator
{
    private static ConfigurationValidator $instance;

    public static function getInstance(): ConfigurationValidator
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(new ConfigurationManager([]));
        }

        return self::$instance;
    }

    public function validate(int $type, string $sectionName, array $values)
    {
        //do not validate
    }
}
