<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Provider;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use PHPUnit\Framework\TestCase;

class PublicDirectoryProviderTest extends TestCase
{
    private string $projectDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/test_project';
        if (!is_dir($this->projectDir)) {
            mkdir($this->projectDir, 0777, true);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testGetPublicDirectoryDefault(): void
    {
        $provider = new PublicDirectoryProvider($this->projectDir);

        self::assertSame($this->projectDir . '/public', $provider->getPublicDirectory());
    }

    public function testGetPublicDirectoryFromComposerConfig(): void
    {
        $composerJsonPath = $this->projectDir . '/composer.json';

        file_put_contents($composerJsonPath, json_encode(['extra' => ['public-dir' => 'custom_public']]));

        $provider = new PublicDirectoryProvider($this->projectDir);

        self::assertSame($this->projectDir . '/custom_public', $provider->getPublicDirectory());
    }

    public function testGetPublicDirectoryInvalidJson(): void
    {
        $composerJsonPath = $this->projectDir . '/composer.json';

        file_put_contents($composerJsonPath, '{invalid json}');

        $provider = new PublicDirectoryProvider($this->projectDir);

        self::assertSame($this->projectDir . '/public', $provider->getPublicDirectory());
    }

    public function testGetPublicDirectoryWhenComposerFileDoesNotExist(): void
    {
        $provider = new PublicDirectoryProvider($this->projectDir);

        self::assertSame($this->projectDir . '/public', $provider->getPublicDirectory());
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $directory . '/' . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($directory);
    }
}
