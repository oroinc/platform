<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Bundle\UIBundle\Twig\AssetSourceExtension;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class AssetSourceExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;
    use TempDirExtension;

    private string $testDir;
    private AssetSourceExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->testDir = $this->getTempDir('asset_source_extension');
        mkdir($this->testDir . '/public', 0777, true);

        $publicDirectoryProvider = $this->createMock(PublicDirectoryProvider::class);

        $publicDirectoryProvider->expects(self::any())
            ->method('getPublicDirectory')
            ->willReturn($this->testDir . '/public');

        $this->extension = new AssetSourceExtension(
            self::getContainerBuilder()
                ->add('oro_distribution.provider.public_directory_provider', $publicDirectoryProvider)
                ->getContainer($this)
        );
    }

    public function testGetFunctions(): void
    {
        self::assertCount(1, $this->extension->getFunctions());
        self::assertSame(
            'asset_source',
            $this->extension->getFunctions()[0]->getName()
        );
    }

    public function testGetAssetSourceReturnsContent(): void
    {
        $relativePath = 'css/sample.css';
        $content = 'body { background: black; }';
        $fullPath = $this->testDir . '/public/' . $relativePath;

        mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, $content);

        self::assertSame(
            $content,
            self::callTwigFunction($this->extension, 'asset_source', [$relativePath])
        );
    }

    public function testGetAssetSourceReturnsEmptyStringIfFileDoesNotExist(): void
    {
        self::assertSame(
            '',
            self::callTwigFunction($this->extension, 'asset_source', ['nonexistent/file.css'])
        );
    }

    public function testGetAssetSourceReturnsEmptyStringIfNotReadable(): void
    {
        $relativePath = 'css/secret.css';
        $fullPath = $this->testDir . '/public/' . $relativePath;

        mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, 'body { background: black; }');
        chmod($fullPath, 0000);

        self::assertSame(
            '',
            self::callTwigFunction($this->extension, 'asset_source', [$relativePath])
        );

        chmod($fullPath, 0644);
    }

    public function testGetAssetSourceReturnsEmptyStringIfPathIsOutsidePublic(): void
    {
        $outsidePath = '../outside.txt';
        $outsideFullPath = realpath($this->testDir) . '/outside.txt';
        file_put_contents($outsideFullPath, 'data');

        self::assertSame(
            '',
            self::callTwigFunction($this->extension, 'asset_source', [$outsidePath])
        );

        unlink($outsideFullPath);
    }
}
