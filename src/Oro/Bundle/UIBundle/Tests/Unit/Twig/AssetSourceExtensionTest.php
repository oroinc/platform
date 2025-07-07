<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Bundle\UIBundle\Twig\AssetSourceExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class AssetSourceExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private string $testDir;

    private AssetSourceExtension $extension;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('asset_source_test_', true);
        mkdir($this->testDir . '/public', 0777, true);

        $publicDirectoryProvider = $this->createMock(PublicDirectoryProvider::class);

        $publicDirectoryProvider
            ->method('getPublicDirectory')
            ->willReturn($this->testDir . '/public');

        $this->extension = new AssetSourceExtension(
            self::getContainerBuilder()
                ->add('oro_distribution.provider.public_directory_provider', $publicDirectoryProvider)
                ->getContainer($this)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
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
            self::callTwigFunction(
                $this->extension,
                'asset_source',
                [$relativePath]
            )
        );
    }

    public function testGetAssetSourceReturnsEmptyStringIfFileDoesNotExist(): void
    {
        self::assertSame(
            '',
            self::callTwigFunction(
                $this->extension,
                'asset_source',
                ['nonexistent/file.css']
            )
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
            self::callTwigFunction(
                $this->extension,
                'asset_source',
                [$relativePath]
            )
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
            self::callTwigFunction(
                $this->extension,
                'asset_source',
                [$outsidePath]
            )
        );

        unlink($outsideFullPath);
    }

    private function removeDirectory(string $dir): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($dir);
    }
}
