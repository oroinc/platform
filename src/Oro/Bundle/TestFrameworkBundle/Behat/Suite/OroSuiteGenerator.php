<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Generates a suite using provided name and settings.
 */
class OroSuiteGenerator implements SuiteGenerator
{
    private const SUITE_TYPE_SYMFONY = 'symfony_bundle';
    private const SUITE_TYPE_GENERIC = null;

    protected KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTypeAndSettings($type, array $settings)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function generateSuite($suiteName, array $settings)
    {
        try {
            $bundleName = $settings['bundle'] ?? $suiteName;
            $bundle = $this->kernel->getBundle($bundleName);
            $settings['type'] = self::SUITE_TYPE_SYMFONY;
            $settings['bundle'] = $bundleName;

            return new SymfonyBundleSuite($suiteName, $bundle, $settings);
        } catch (\InvalidArgumentException $e) {
            $settings['type'] = self::SUITE_TYPE_GENERIC;

            return new OroGenericSuite($suiteName, $settings, $this->kernel->getProjectDir());
        }
    }
}
