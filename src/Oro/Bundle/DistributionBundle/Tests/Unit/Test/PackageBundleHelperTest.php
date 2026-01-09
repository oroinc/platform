<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Test;

use Oro\Bundle\DistributionBundle\Test\PackageBundleHelper;

class PackageBundleHelperTest extends \PHPUnit\Framework\TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $dir = __DIR__ . '/../Fixtures/packages/';
        require_once $dir . 'multi-bundle/src/Oro/Bundle/FirstBundle/Tests/Functional/MultiBundleFunctionalTest.php';
        require_once $dir . 'multi-bundle/src/Oro/Bundle/SecondBundle/Tests/Unit/MultiBundleUnitTest.php';
        require_once $dir . 'single-bundle/Tests/Functional/SingleBundleFunctionalTest.php';
        require_once $dir . 'single-bundle/Tests/Unit/SingleBundleUnitTest.php';
        require_once $dir . 'orolab-multi/src/OroLab/Bundle/FirstBundle/Tests/Functional/OroLabMultiBundleTest.php';
        require_once $dir . 'laboro-multi/src/LabOro/Bundle/TestBundle/Tests/Functional/LabOroMultiBundleTest.php';
    }

    public function testGetPackageBundleNamespacesFromMultiBundlePackageFunctionalTest(): void
    {
        $testInstance = new \Oro\Bundle\FirstBundle\Tests\Functional\MultiBundleFunctionalTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(3, $namespaces);
        $this->assertContains('Oro\Bundle\FirstBundle', $namespaces);
        $this->assertContains('Oro\Bundle\SecondBundle', $namespaces);
        $this->assertContains('Oro\Bundle\ThirdBundle', $namespaces);
    }

    public function testGetPackageBundleNamespacesFromMultiBundlePackageUnitTest(): void
    {
        $testInstance = new \Oro\Bundle\SecondBundle\Tests\Unit\MultiBundleUnitTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(3, $namespaces);
        $this->assertContains('Oro\Bundle\FirstBundle', $namespaces);
        $this->assertContains('Oro\Bundle\SecondBundle', $namespaces);
        $this->assertContains('Oro\Bundle\ThirdBundle', $namespaces);
    }

    public function testGetPackageBundleNamespacesFromSingleBundlePackageFunctionalTest(): void
    {
        $testInstance = new \Acme\SinglePackage\Tests\Functional\SingleBundleFunctionalTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(1, $namespaces);
        $this->assertSame(['Acme\SinglePackage'], $namespaces);
    }

    public function testGetPackageBundleNamespacesFromSingleBundlePackageUnitTest(): void
    {
        $testInstance = new \Acme\SinglePackage\Tests\Unit\SingleBundleUnitTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(1, $namespaces);
        $this->assertSame(['Acme\SinglePackage'], $namespaces);
    }

    public function testGetPackageBundleNamespacesFromOroLabMultiBundlePackage(): void
    {
        $testInstance = new \OroLab\Bundle\FirstBundle\Tests\Functional\OroLabMultiBundleTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(2, $namespaces);
        $this->assertContains('OroLab\Bundle\FirstBundle', $namespaces);
        $this->assertContains('OroLab\Bundle\SecondBundle', $namespaces);
    }

    public function testGetPackageBundleNamespacesFromLabOroMultiBundlePackage(): void
    {
        $testInstance = new \LabOro\Bundle\TestBundle\Tests\Functional\LabOroMultiBundleTest();
        $namespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($testInstance);

        $this->assertIsArray($namespaces);
        $this->assertCount(1, $namespaces);
        $this->assertSame(['LabOro\Bundle\TestBundle'], $namespaces);
    }
}
