<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Provider;

use Oro\Bundle\InstallerBundle\Provider\PublicMediaDirectoryRequirementsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Requirements\RequirementCollection;

class PublicMediaDirectoryRequirementsProviderTest extends TestCase
{
    private string $mediaDirectory;
    private PublicMediaDirectoryRequirementsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $projectDirectory = sys_get_temp_dir();
        $this->mediaDirectory = '/public/media';

        $this->provider = new PublicMediaDirectoryRequirementsProvider($projectDirectory);

        $this->tempDir = sys_get_temp_dir() . $this->mediaDirectory;
        mkdir($this->tempDir, 0777, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        chmod($this->tempDir, 0777);
        rmdir($this->tempDir);
        rmdir(sys_get_temp_dir() . '/public');
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
