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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns suite bundle.
     *
     * @return BundleInterface
     */
    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSetting($key)
    {
        return isset($this->settings[$key]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ParameterNotFoundException If setting is not set
     */
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
