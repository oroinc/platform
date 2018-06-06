<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\ComposerInstallerStub;
use Symfony\Component\Config\FileLocatorInterface;

abstract class TranslationPackagesProviderExtensionTestAbstract extends WebTestCase
{
    /** @var TranslationPackageProvider */
    protected $provider;

    protected function setUp()
    {
        $this->initClient();

        $this->setUpComposer($this->getPackageName());

        $this->provider = $this->getContainer()->get('oro_translation.packages_provider.translation');
    }

    /**
     * @dataProvider expectedPackagesDataProvider
     *
     * @param $packageName
     */
    public function testGetInstalledPackages($packageName)
    {
        $this->assertContains($packageName, $this->provider->getInstalledPackages());
    }

    /**
     * @dataProvider expectedPackagesDataProvider
     *
     * @param $packageName
     * @param $fileToLocate
     */
    public function testGetTranslationPackageProviderByPackageName($packageName, $fileToLocate)
    {
        $extension = $this->provider->getTranslationPackageProviderByPackageName($packageName);

        $this->assertInstanceOf(TranslationPackagesProviderExtensionInterface::class, $extension);
        $this->assertContains($packageName, $extension->getPackageNames());

        $fileLocator = $extension->getPackagePaths();
        $this->assertInstanceOf(FileLocatorInterface::class, $fileLocator);
        $this->assertInternalType('string', $fileLocator->locate($fileToLocate));
    }

    /**
     * @param string $packageName
     */
    protected function setUpComposer(string $packageName)
    {
        $container = $this->getContainer();

        $installer = new ComposerInstallerStub(
            sprintf(
                '%s/vendor/oro/%s',
                rtrim(
                    $container->getParameter('kernel.project_dir'),
                    '/'
                ),
                $packageName
            )
        );

        $installationManager = new InstallationManager();
        $installationManager->addInstaller($installer);

        $composer = new Composer();
        $composer->setInstallationManager($installationManager);

        $container->set('oro_distribution.composer.installation_manager', $installationManager);
        $container->set('oro_distribution.composer', $composer);
    }

    /**
     * @return array|\Generator
     */
    abstract public function expectedPackagesDataProvider();

    /**
     * @return array|\Generator
     */
    abstract protected function getPackageName();
}
