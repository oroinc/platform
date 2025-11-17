<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Provider;

use Oro\Bundle\InstallerBundle\Provider\PublicMediaDirectoryRequirementsProvider;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Requirements\RequirementCollection;

class PublicMediaDirectoryRequirementsProviderTest extends TestCase
{
    use TempDirExtension;

    private string $mediaDirectory;
    private string $tempDir;
    private PublicMediaDirectoryRequirementsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $projectDirectory = $this->getTempDir('public_media_dir_requirements_provider');
        $this->mediaDirectory = '/public/media';

        $this->provider = new PublicMediaDirectoryRequirementsProvider($projectDirectory);

        $this->tempDir = $projectDirectory . $this->mediaDirectory;
        mkdir($this->tempDir, 0777, true);
    }

    public function testGetOroRequirements(): void
    {
        $collection = $this->provider->getOroRequirements();

        $this->assertInstanceOf(RequirementCollection::class, $collection);

        $requirements = $collection->all();
        $this->assertNotEmpty($requirements);
        $this->assertStringContainsString(
            'public/media directory must be writable',
            $requirements[0]->getTestMessage()
        );
    }

    public function testAddPathWritableRequirementIsFulfilled(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('addPathWritableRequirement');

        $collection = new RequirementCollection();
        $method->invoke($this->provider, $collection, $this->mediaDirectory);

        $requirements = $collection->all();
        $this->assertNotEmpty($requirements);
        $this->assertTrue($requirements[0]->isFulfilled());
    }

    public function testAddPathWritableRequirementIsNotFulfilled(): void
    {
        // Change permissions to Read-Only
        chmod($this->tempDir, 0555);

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('addPathWritableRequirement');

        $collection = new RequirementCollection();
        $method->invoke($this->provider, $collection, $this->mediaDirectory);

        $requirements = $collection->all();
        $this->assertNotEmpty($requirements);
        $this->assertFalse($requirements[0]->isFulfilled());
    }
}
