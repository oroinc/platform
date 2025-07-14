<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;

class PackageProviderTest extends TestCase
{
    protected TestablePackageProvider $provider;

    public function testGetThirdPartyPackagesEmpty(): void
    {
        $provider = new TestablePackageProvider();
        $provider->setInstalledPackages(['oro/platform' => ['pretty_version' => '1.0.0']]);
        $this->assertEmpty($provider->getThirdPartyPackages());
    }

    public function testGetThirdPartyPackages(): void
    {
        $provider = new TestablePackageProvider();
        $provider->setInstalledPackages([
            'oro/platform' => ['pretty_version' => '1.0.0'],
            'not-oro/platform' => ['pretty_version' => '1.0.0']
        ]);
        $this->assertEquals(
            ['not-oro/platform' => ['pretty_version' => '1.0.0', 'license' => []]],
            $provider->getThirdPartyPackages()
        );
    }

    public function testGetOroPackagesEmpty(): void
    {
        $provider = new TestablePackageProvider();
        $provider->setInstalledPackages(['not-oro/platform' => ['pretty_version' => '1.0.0']]);
        $this->assertEmpty($provider->getOroPackages());
    }

    public function testGetOroPackages(): void
    {
        $provider = new TestablePackageProvider();
        $provider->setInstalledPackages([
            'oro/platform' => ['pretty_version' => '1.0.0'],
            'not-oro/platform' => ['pretty_version' => '1.0.0']
        ]);
        $this->assertEquals(
            ['oro/platform' => ['pretty_version' => '1.0.0', 'license' => []]],
            $provider->getOroPackages()
        );
    }

    public function testFilterOroPackages(): void
    {
        $provider = new TestablePackageProvider();
        $provider->setInstalledPackages([
            'oro/platform' => ['pretty_version' => '1.0.0'],
            'oro/some-extension' => ['pretty_version' => '1.0.0']
        ]);
        $this->assertEquals(
            ['oro/platform' => ['pretty_version' => '1.0.0', 'license' => []]],
            $provider->getOroPackages(false)
        );
        $this->assertEquals(
            [
                'oro/platform' => ['pretty_version' => '1.0.0', 'license' => []],
                'oro/some-extension' => ['pretty_version' => '1.0.0', 'license' => []]
            ],
            $provider->getOroPackages()
        );
        $this->assertEmpty($provider->getThirdPartyPackages());
    }

    public function testLicenseRetrieval(): void
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'installed.json';
        $provider = new TestablePackageProvider($file);
        $provider->setInstalledPackages([
            'oro/platform' => ['pretty_version' => '1.0.0'],
            'oro/crm' => ['pretty_version' => '1.0.0']
        ]);

        $this->assertEquals(
            [
                'oro/platform' => ['pretty_version' => '1.0.0', 'license' => ['MIT']],
                'oro/crm' => ['pretty_version' => '1.0.0', 'license' => ['OSL-3.0']]
            ],
            $provider->getOroPackages()
        );
    }

    public function testLicenseRetrievalFailure(): void
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'wrong.json';
        $this->expectErrorMessage(sprintf('File "%s" does not exists.', $file));

        $provider = new TestablePackageProvider($file);
        $provider->setInstalledPackages(['oro/platform' => ['pretty_version' => '1.0.0']]);
        $provider->getOroPackages();
    }
}
