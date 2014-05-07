<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryManager;

class LocalRepositoryManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalRepositoryManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->manager = new LocalRepositoryManager(
            $this->getFilesystem(true),
            __DIR__ . DIRECTORY_SEPARATOR,
            'fixtures' . DIRECTORY_SEPARATOR . 'installed.json'
        );
    }

    public function testGetRepository()
    {
        $repository = $this->manager->getLocalRepository();
        $this->assertInstanceOf('Composer\Repository\InstalledFilesystemRepository', $repository);

        $packages = $repository->getCanonicalPackages();
        $this->assertCount(2, $repository->getCanonicalPackages());

        $this->assertEquals('oro/platform', $packages[0]->getName());
        $this->assertEquals('oro/crm', $packages[1]->getName());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage File "vendor/file" does not exists
     */
    public function testGetRepositoryFail()
    {
        new LocalRepositoryManager($this->getFilesystem(false), 'vendor/', 'file');
    }

    /**
     * @param bool $isExists
     * @return Filesystem
     */
    protected function getFilesystem($isExists)
    {
        $fs = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $fs
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue($isExists));

        return $fs;
    }
}
