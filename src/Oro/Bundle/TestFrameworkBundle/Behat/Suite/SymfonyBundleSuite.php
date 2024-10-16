<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Represents test suite with the specific attribute 'bundle'.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class SymfonyBundleSuite implements Suite
{
    private string $name;

    private BundleInterface $bundle;

    private array $settings;

    public function __construct(string $name, BundleInterface $bundle, array $settings)
    {
        $this->name = $name;
        $this->bundle = $bundle;
        $this->settings = $settings;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns suite bundle.
     */
    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }

    #[\Override]
    public function getSettings()
    {
        return $this->settings;
    }

    #[\Override]
    public function hasSetting($key)
    {
        return isset($this->settings[$key]);
    }

    /**
     *
     * @throws ParameterNotFoundException If setting is not set
     */
    #[\Override]
    public function getSetting($key)
    {
        if (!$this->hasSetting($key)) {
            throw new ParameterNotFoundException(
                sprintf(
                    '`%s` suite does not have a `%s` setting.',
                    $this->getName(),
                    $key
                ),
                $this->getName(),
                $key
            );
        }

        return $this->settings[$key];
    }
}
