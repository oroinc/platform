<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourceCheckerConfigProvider;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ResourceCheckerConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ResourceCheckerConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ResourceCheckerConfigProvider(
            $this->getTempFile('ResourceCheckerConfigProvider')
        );
    }

    private function buildCache(): void
    {
        $this->provider->startBuild();
        $this->provider->addApiResource('feature1', 'Entity1', ['get']);
        $this->provider->addApiResource('feature1', 'Entity1', ['create', 'update']);
        $this->provider->addApiResource('feature1', 'Entity2', ['update']);
        $this->provider->addApiResource('feature2', 'Entity1', ['update']);
        $this->provider->flush();
    }

    public function testGetApiResourceFeaturesWhenNoCacheFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->provider->getApiResourceFeatures('Entity1', 'get');
    }

    public function testGetApiResourceFeatures(): void
    {
        $this->buildCache();

        self::assertEquals(['feature1'], $this->provider->getApiResourceFeatures('Entity1', 'get'));
        self::assertEquals(['feature1'], $this->provider->getApiResourceFeatures('Entity1', 'create'));
        self::assertEquals(['feature1', 'feature2'], $this->provider->getApiResourceFeatures('Entity1', 'update'));
        self::assertEquals(['feature1'], $this->provider->getApiResourceFeatures('Entity2', 'update'));
        self::assertEquals([], $this->provider->getApiResourceFeatures('Entity2', 'get'));
        self::assertEquals([], $this->provider->getApiResourceFeatures('Entity3', 'get'));
    }

    public function testGetApiResourcesWhenNoCacheFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->provider->getApiResources('feature1');
    }

    public function testGetApiResources(): void
    {
        $this->buildCache();

        self::assertEquals(
            [
                ['Entity1', ['get', 'create', 'update']],
                ['Entity2', ['update']]
            ],
            $this->provider->getApiResources('feature1')
        );
        self::assertEquals(
            [
                ['Entity1', ['update']]
            ],
            $this->provider->getApiResources('feature2')
        );
        self::assertEquals([], $this->provider->getApiResources('feature3'));
    }
}
