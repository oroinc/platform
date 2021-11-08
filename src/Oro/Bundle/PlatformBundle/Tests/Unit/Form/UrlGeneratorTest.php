<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Form;

use Composer\Package\Package;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\PlatformBundle\Form\UrlGenerator;
use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

class UrlGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PackageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $packageProvider;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var UrlGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->cacheProvider = $this->createMock(ArrayCache::class);

        $this->generator = new UrlGenerator($this->packageProvider, $this->cacheProvider);
    }

    public function testGetFormUrlCacheHit()
    {
        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->willReturn(UrlGenerator::URL);

        $this->assertEquals(UrlGenerator::URL, $this->generator->getFormUrl());
    }

    /**
     * @dataProvider urlDataProvider
     */
    public function testGetFormUrl(string $expectedUrl, array $packages = [])
    {
        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->willReturn(false);
        $this->cacheProvider->expects($this->never())
            ->method('fetch');
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($this->isType('string'), $expectedUrl);

        $this->packageProvider->expects($this->once())
            ->method('getOroPackages')
            ->with(false)
            ->willReturn($packages);

        $this->assertEquals($expectedUrl, $this->generator->getFormUrl());
    }

    public function urlDataProvider(): array
    {
        return [
            'beta commerce packages' => [
                '//r.orocrm.com/a/p/1.10.0/o/1.0.0-beta.3/r/1.10.0/f.js',
                [
                    $this->getPackage('oro/platform', '1.10.0'),
                    $this->getPackage('oro/commerce', '1.0.0-beta.3'),
                    $this->getPackage('oro/crm', '1.10.0'),
                ],
            ],
            'dev-master versions' => [
                '//r.orocrm.com/a/p/dev-master/o/dev-master/f.js',
                [
                    $this->getPackage('oro/platform', 'dev-master'),
                    $this->getPackage('oro/commerce', 'dev-master'),
                ],
            ],
            'crm versions' => [
                '//r.orocrm.com/a/p/1.10.0/r/1.10.0/f.js',
                [
                    $this->getPackage('oro/platform', '1.10.0'),
                    $this->getPackage('oro/crm', '1.10.0'),
                ],
            ],
            'platform only' => [
                '//r.orocrm.com/a/p/1.10.0/f.js',
                [
                    $this->getPackage('oro/platform', '1.10.0'),
                ],
            ],
            'no packages' => ['//r.orocrm.com/a/f.js'],
            'do not include extensions' => [
                '//r.orocrm.com/a/p/1.10.0/f.js',
                [
                    $this->getPackage('oro/platform', '1.10.0'),
                    $this->getPackage('oro/doctrine-extensions', '1.1.0'),
                ],
            ],
        ];
    }

    private function getPackage(string $name, string $version): Package
    {
        return new Package($name, $version, $version);
    }
}
