<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Symfony\Component\Config\FileLocatorInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

abstract class TranslationPackagesProviderExtensionTestAbstract extends WebTestCase
{
    /** @var TranslationPackageProvider */
    protected $provider;

    protected function setUp()
    {
        $this->initClient();

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
     * @return array|\Generator
     */
    abstract public function expectedPackagesDataProvider();
}
