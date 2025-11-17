<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Provider;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

class PublicDirectoryProviderTest extends TestCase
{
    use TempDirExtension;

    private string $projectDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectDir = $this->getTempDir('public_dir_provider') . '/test_project';
        mkdir($this->projectDir, 0777, true);
    }

    public function testGetPublicDirectoryDefault(): void
    {
        $provider = new PublicDirectoryProvider($this->projectDir);

        self::assertSame($this->projectDir . '/public', $provider->getPublicDirectory());
    }

    public function testGetPublicDirectoryFromComposerConfig(): void
    {
        $composerJsonPath = $this->projectDir . '/composer.json';

        file_put_contents(
            $composerJsonPath,
            json_encode(['extra' => ['public-dir' => 'custom_public']], JSON_THROW_ON_ERROR)
        );

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
}
