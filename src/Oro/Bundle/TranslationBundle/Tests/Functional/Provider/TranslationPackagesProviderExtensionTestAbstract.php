<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;

abstract class TranslationPackagesProviderExtensionTestAbstract extends WebTestCase
{
    /** @var TranslationPackageProvider */
    protected $provider;

    protected function setUp(): void
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
        static::markTestSkipped('This test is removed in BAP-20358');
    }

    /**
     * @dataProvider expectedPackagesDataProvider
     *
     * @param $packageName
     * @param $fileToLocate
     */
    public function testGetTranslationPackageProviderByPackageName($packageName, $fileToLocate)
    {
        static::markTestSkipped('This test is removed in BAP-20358');
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
