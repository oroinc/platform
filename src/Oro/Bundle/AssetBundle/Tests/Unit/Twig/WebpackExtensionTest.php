<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Twig;

use Oro\Bundle\AssetBundle\Twig\WebpackExtension;
use Oro\Bundle\AssetBundle\Webpack\WebpackServer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebpackExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private WebpackServer&MockObject $webpackServer;
    private WebpackExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->webpackServer = $this->createMock(WebpackServer::class);

        $container = self::getContainerBuilder()
            ->add('oro_asset.webpack_server', $this->webpackServer)
            ->getContainer($this);

        $this->extension = new WebpackExtension($container);
    }

    /**
     * @dataProvider webpackHmrEnabledDataProvider
     */
    public function testWebpackHmrEnabled(bool $enabled): void
    {
        $this->webpackServer->expects(self::once())
            ->method('isRunning')
            ->willReturn($enabled);

        self::assertSame(
            $enabled,
            self::callTwigFunction($this->extension, 'webpack_hmr_enabled', [])
        );
    }

    public function webpackHmrEnabledDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }

    public function testWebpackAsset(): void
    {
        $url = 'src-url';
        $serverUrl = 'server-url';

        $this->webpackServer->expects(self::once())
            ->method('getServerUrl')
            ->with($url)
            ->willReturn($serverUrl);

        $this->assertSame(
            $serverUrl,
            self::callTwigFilter($this->extension, 'webpack_asset', [$url])
        );
    }

    public function testWebpackAssetWhenUrlIsNotSpecified(): void
    {
        $serverUrl = 'server-url';

        $this->webpackServer->expects(self::once())
            ->method('getServerUrl')
            ->with(self::identicalTo(''))
            ->willReturn($serverUrl);

        $this->assertSame(
            $serverUrl,
            self::callTwigFilter($this->extension, 'webpack_asset', [])
        );
    }
}
