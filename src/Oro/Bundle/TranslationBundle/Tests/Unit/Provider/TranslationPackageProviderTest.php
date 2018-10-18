<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

class TranslationPackageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationPackageProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = new TranslationPackageProvider();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->provider);
    }

    /**
     * @dataProvider extensionDataProvider
     *
     * @param array $extensions
     * @param array $expects
     */
    public function testGetInstalledPackages(array $extensions, array $expects)
    {
        foreach ($extensions as $extension) {
            $this->provider->addExtension($extension);
        }

        $this->assertSame($expects, $this->provider->getInstalledPackages());
    }

    /**
     * @return \Generator
     */
    public function extensionDataProvider()
    {
        $extension1 = $this->createExtension([]);
        $extension2 = $this->createExtension(['package1', 'package2']);
        $extension3 = $this->createExtension(['package3', 'package2']);

        yield 'empty packages' => [
            'extensions' => [$extension1],
            'expects' => []
        ];

        yield 'contains packages' => [
            'extensions' => [$extension1, $extension2],
            'expects' => ['package1', 'package2']
        ];

        yield 'packages merged from two extensions' => [
            'extensions' => [$extension2, $extension3],
            'expects' => ['package1', 'package2', 'package3']
        ];
    }

    /**
     * @dataProvider packageProvider
     *
     * @param array $extensions
     * @param string $name
     * @param TranslationPackagesProviderExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $expects
     */
    public function testGetTranslationPackageProviderByPackageName(array $extensions, $name, $expects)
    {
        foreach ($extensions as $extension) {
            $this->provider->addExtension($extension);
        }

        $this->assertSame($expects, $this->provider->getTranslationPackageProviderByPackageName($name));
    }


    /**
     * @return \Generator
     */
    public function packageProvider()
    {
        $extension1 = $this->createExtension([]);
        $extension2 = $this->createExtension(['package1', 'package2']);

        yield 'empty packages' => [
            'extensions' => [],
            'name' => 'package1',
            'expects' => null
        ];

        yield 'test when not found packages' => [
            'extensions' => [$extension1, $extension2],
            'name' => 'package3',
            'expects' => null
        ];

        yield 'test when found packages' => [
            'extensions' => [$extension1, $extension2],
            'name' => 'package2',
            'expects' => $extension2
        ];
    }

    /**
     * @param array|string[] $names
     * @return TranslationPackagesProviderExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createExtension(array $names)
    {
        $extension = $this->createMock(TranslationPackagesProviderExtensionInterface::class);
        $extension->expects($this->any())->method('getPackageNames')->willReturn($names);

        return $extension;
    }
}
