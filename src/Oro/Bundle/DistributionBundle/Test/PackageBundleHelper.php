<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Test;

/**
 * Helper class for detecting bundle namespaces in multi-bundle and single-bundle packages.
 */
class PackageBundleHelper
{
    /**
     * Gets all bundle namespaces for the package containing the given test class.
     *
     * For multi-bundle packages (e.g., commerce, platform):
     *   - Test is in .../Vendor/Bundle/SomeBundle/Tests/... (e.g., Oro/Bundle, OroLab/Bundle, etc.)
     *   - Goes up to Vendor/Bundle and returns all bundles
     *
     * For single-bundle packages (e.g., call, calendar):
     *   - Test is in .../package/call/Tests/...
     *   - Parent of Tests is not a *Bundle directory, returns only the current bundle
     *
     * @param object $testInstance Instance of the test class
     * @return string[] Array of bundle namespaces (e.g., ['Oro\Bundle\ApplicationBundle', ...])
     */
    public static function getPackageBundleNamespacesFromTestClass(object $testInstance): array
    {
        $testReflection = new \ReflectionClass($testInstance);
        $testFilePath = $testReflection->getFileName();

        // Go up to the Tests directory
        $testsPath = \dirname($testFilePath);
        while (\basename($testsPath) !== 'Tests' && $testsPath !== \dirname($testsPath)) {
            $testsPath = \dirname($testsPath);
        }

        // One directory up from Tests
        $parentDir = \dirname($testsPath);
        $parentDirName = \basename($parentDir);

        // Check if the parent directory is a bundle (ends with "Bundle")
        if (\str_ends_with($parentDirName, 'Bundle')) {
            // Multi-bundle package: go up to */Bundle and get all bundles
            // Parent is something like .../Vendor/Bundle/ApplicationBundle
            $bundleContainerPath = \dirname($parentDir);
            if (\basename($bundleContainerPath) === 'Bundle') {
                // Get the vendor namespace (e.g., "Oro", "OroLab", "Custom", etc.)
                $vendorPath = \dirname($bundleContainerPath);
                $vendorName = \basename($vendorPath);
                return self::getAllBundlesInDirectory($bundleContainerPath, $vendorName);
            }
        }

        // Single-bundle package: return only this bundle's namespace
        $testNamespace = $testReflection->getNamespaceName();
        $bundleNamespace = \preg_replace('/\\\\Tests\\\\.*$/', '', $testNamespace);
        return [$bundleNamespace];
    }

    /**
     * Gets all bundle namespaces in the given Vendor/Bundle directory.
     *
     * @param string $bundleContainerPath Path to Vendor/Bundle directory (e.g., .../Oro/Bundle)
     * @param string $vendorName Vendor namespace name (e.g., "Oro", "OroLab", "Custom")
     * @return string[] Array of bundle namespaces
     */
    private static function getAllBundlesInDirectory(string $bundleContainerPath, string $vendorName): array
    {
        $namespaces = [];
        $bundleDirs = \glob($bundleContainerPath . '/*', GLOB_ONLYDIR);

        if ($bundleDirs === false) {
            return [];
        }

        foreach ($bundleDirs as $bundleDir) {
            $bundleName = \basename($bundleDir);
            $namespaces[] = "{$vendorName}\\Bundle\\{$bundleName}";
        }

        return $namespaces;
    }
}
