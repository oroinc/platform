<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Oro\Bundle\TranslationBundle\OroTranslationBundle;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtension;

class TranslationPackagesProviderExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var WritableRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var InstallationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var TranslationPackagesProviderExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(WritableRepositoryInterface::class);

        /** @var LocalRepositoryFactory|\PHPUnit\Framework\MockObject\MockObject $localRepositoryFactory */
        $localRepositoryFactory = $this->createMock(LocalRepositoryFactory::class);
        $localRepositoryFactory->expects($this->any())->method('getLocalRepository')->willReturn($this->repository);

        $this->manager = $this->createMock(InstallationManager::class);

        /** @var Composer|\PHPUnit\Framework\MockObject\MockObject $composer */
        $composer = $this->createMock(Composer::class);
        $composer->expects($this->any())->method('getInstallationManager')->willReturn($this->manager);

        $this->extension = new TranslationPackagesProviderExtension($localRepositoryFactory, $composer);
        $this->extension->addPackage('Oro', 'oro/platform', '/src');
    }

    public function testGetPackageNames()
    {
        $this->extension->addPackage('OroTest', 'oro/platform');
        $this->extension->addPackage('OroTest', 'oro/platform');

        $this->assertEquals(['Oro', 'OroTest'], $this->extension->getPackageNames());
    }

    public function testGetPackagePaths()
    {
        $package = $this->createMock(PackageInterface::class);

        $this->repository->expects($this->once())
            ->method('findPackage')
            ->with('oro/platform', '*')
            ->willReturn($package);

        $this->manager->expects($this->once())
            ->method('getInstallPath')
            ->with($package)
            ->willReturn(__DIR__ . '/../../../../../../..');

        $path = str_replace('\\', '/', sprintf('%s.php', OroTranslationBundle::class));

        $this->assertNotEmpty($this->extension->getPackagePaths()->locate($path));
    }
}
