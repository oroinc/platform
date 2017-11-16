<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\GenericSuite;
use Symfony\Component\HttpKernel\KernelInterface;

class OroSuiteGenerator implements SuiteGenerator
{
    const SUITE_TYPE_SYMFONY = 'symfony_bundle';
    const SUITE_TYPE_GENERIC = null;

    /**
     * @var KernelInterface
     */
    protected $kernel;

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
            $bundleName = isset($settings['bundle']) ? $settings['bundle'] : $suiteName;
            $bundle = $this->kernel->getBundle('!' . $bundleName);
            $settings['type'] = self::SUITE_TYPE_SYMFONY;
            $settings['bundle'] = $bundleName;

            return new SymfonyBundleSuite($suiteName, $bundle, $settings);
        } catch (\InvalidArgumentException $e) {
            $settings['type'] = self::SUITE_TYPE_GENERIC;

            return new GenericSuite($suiteName, $settings);
        }
    }
}
