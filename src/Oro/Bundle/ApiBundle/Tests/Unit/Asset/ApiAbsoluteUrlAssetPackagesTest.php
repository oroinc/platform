<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Asset;

use Oro\Bundle\ApiBundle\Asset\ApiAbsoluteUrlAssetPackages;
use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiAbsoluteUrlAssetPackagesTest extends TestCase
{
    private Packages&MockObject $innerPackages;
    private RequestStack $requestStack;
    private ApiUrlResolver&MockObject $apiUrlResolver;
    private ApiAbsoluteUrlAssetPackages $packages;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerPackages = $this->createMock(Packages::class);
        $this->requestStack = new RequestStack();
        $this->apiUrlResolver = $this->createMock(ApiUrlResolver::class);

        $this->packages = new ApiAbsoluteUrlAssetPackages(
            $this->innerPackages,
            $this->requestStack,
            $this->apiUrlResolver
        );
    }

    public function testGetUrlWhenAbsoluteUrlsNotRequired(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(false);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/build/default/icon.svg')
            ->willReturn('/build/default/icon.svg');

        self::assertSame(
            '/build/default/icon.svg',
            $this->packages->getUrl('/build/default/icon.svg')
        );
    }

    public function testGetUrlWhenAbsoluteUrlsRequiredForBuildPath(): void
    {
        $request = Request::create('https://example.com/api/test');
        $this->requestStack->push($request);

        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/build/default/icon.svg')
            ->willReturn('/build/default/icon.svg');

        self::assertSame(
            'https://example.com/build/default/icon.svg',
            $this->packages->getUrl('/build/default/icon.svg')
        );
    }

    public function testGetUrlWhenAbsoluteUrlsRequiredButUrlAlreadyAbsolute(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/build/default/icon.svg')
            ->willReturn('https://cdn.example.com/build/default/icon.svg');

        // Should not modify already absolute URLs
        self::assertSame(
            'https://cdn.example.com/build/default/icon.svg',
            $this->packages->getUrl('/build/default/icon.svg')
        );
    }

    public function testGetUrlWhenAbsoluteUrlsRequiredButNotBuildPath(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/images/logo.png')
            ->willReturn('/images/logo.png');

        // Should not convert non-/build/ paths
        self::assertSame(
            '/images/logo.png',
            $this->packages->getUrl('/images/logo.png')
        );
    }

    public function testGetUrlWhenAbsoluteUrlsRequiredButNoRequest(): void
    {
        // No request in stack
        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/build/default/icon.svg')
            ->willReturn('/build/default/icon.svg');

        // Should return relative URL when no request available
        self::assertSame(
            '/build/default/icon.svg',
            $this->packages->getUrl('/build/default/icon.svg')
        );
    }

    public function testGetUrlWithPackageName(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(false);

        $this->innerPackages->expects(self::once())
            ->method('getUrl')
            ->with('/build/icon.svg', 'custom_package')
            ->willReturn('/custom/build/icon.svg');

        self::assertSame(
            '/custom/build/icon.svg',
            $this->packages->getUrl('/build/icon.svg', 'custom_package')
        );
    }

    public function testGetPackage(): void
    {
        $package = $this->createMock(PackageInterface::class);

        $this->innerPackages->expects(self::once())
            ->method('getPackage')
            ->with('custom_package')
            ->willReturn($package);

        self::assertSame($package, $this->packages->getPackage('custom_package'));
    }
}
