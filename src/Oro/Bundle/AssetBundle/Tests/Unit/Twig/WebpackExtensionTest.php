<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Twig;

use Oro\Bundle\AssetBundle\Twig\WebpackExtension;
use Oro\Bundle\AssetBundle\Webpack\WebpackServer;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WebpackExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WebpackServer|\PHPUnit\Framework\MockObject\MockObject */
    private $webpackServer;

    /** @var UiExtension */
    private $extension;

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
    public function testWebpackHmrEnabled(bool $enabled)
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

    public function testWebpackAsset()
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

    public function testWebpackAssetWhenUrlIsNotSpecified()
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
