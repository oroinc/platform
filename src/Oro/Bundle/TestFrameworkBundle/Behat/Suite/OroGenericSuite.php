<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;
use Behat\Testwork\Suite\Suite;

/**
 * Represents generic (no specific attributes) test suite.
 */
class OroGenericSuite implements Suite
{
    public function __construct(private string $name, private array $settings, private string $projectDir)
    {
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function hasSetting($key): bool
    {
        return array_key_exists($key, $this->settings);
    }

    public function getSetting($key): mixed
    {
        if (!$this->hasSetting($key)) {
            throw new ParameterNotFoundException(sprintf(
                '`%s` suite does not have a `%s` setting.',
                $this->getName(),
                $key
            ), $this->getName(), $key);
        }

        return $this->settings[$key];
    }
}
