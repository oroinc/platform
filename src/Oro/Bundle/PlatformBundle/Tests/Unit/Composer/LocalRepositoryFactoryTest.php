<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Composer\Repository\InstalledFilesystemRepository;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;

class LocalRepositoryFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRepository()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'installed.json';
        $factory = new LocalRepositoryFactory($file);

        $repository = $factory->getLocalRepository();
        $this->assertInstanceOf(InstalledFilesystemRepository::class, $repository);

        $packages = $repository->getCanonicalPackages();
        $this->assertCount(2, $repository->getCanonicalPackages());

        $this->assertEquals(OroPlatformBundle::PACKAGE_NAME, $packages[0]->getName());
        $this->assertEquals('oro/crm', $packages[1]->getName());
    }

    public function testGetRepositoryWhenFileDoesNotExist()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'not_existing.json';
        $factory = new LocalRepositoryFactory($file);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('File "%s" does not exists.', $file));

        $factory->getLocalRepository();
    }
}
