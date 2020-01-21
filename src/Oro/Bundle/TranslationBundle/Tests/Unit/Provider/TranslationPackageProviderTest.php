<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

class TranslationPackageProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider extensionDataProvider
     */
    public function testGetInstalledPackages(array $extensions, array $expects)
    {
        $provider = new TranslationPackageProvider($extensions);
        $this->assertSame($expects, $provider->getInstalledPackages());
    }

    public function extensionDataProvider()
    {
        $extension1 = $this->createExtension([]);
        $extension2 = $this->createExtension(['package1', 'package2']);
        $extension3 = $this->createExtension(['package3', 'package2']);

        return [
            'empty packages'                      => [
                'extensions' => [$extension1],
                'expects'    => []
            ],
            'contains packages'                   => [
                'extensions' => [$extension1, $extension2],
                'expects'    => ['package1', 'package2']
            ],
            'packages merged from two extensions' => [
                'extensions' => [$extension2, $extension3],
                'expects'    => ['package1', 'package2', 'package3']
            ]
        ];
    }

    /**
     * @dataProvider packageProvider
     */
    public function testGetTranslationPackageProviderByPackageName(array $extensions, $name, $expects)
    {
        $provider = new TranslationPackageProvider($extensions);
        $this->assertSame($expects, $provider->getTranslationPackageProviderByPackageName($name));
    }

    public function packageProvider()
    {
        $extension1 = $this->createExtension([]);
        $extension2 = $this->createExtension(['package1', 'package2']);

        return [
            'empty packages'               => [
                'extensions' => [],
                'name'       => 'package1',
                'expects'    => null
            ],
            'test when not found packages' => [
                'extensions' => [$extension1, $extension2],
                'name'       => 'package3',
                'expects'    => null
            ],
            'test when found packages'     => [
                'extensions' => [$extension1, $extension2],
                'name'       => 'package2',
                'expects'    => $extension2
            ]
        ];
    }

    /**
     * @param string[] $names
     *
     * @return TranslationPackagesProviderExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createExtension(array $names)
    {
        $extension = $this->createMock(TranslationPackagesProviderExtensionInterface::class);
        $extension->expects($this->any())
            ->method('getPackageNames')
            ->willReturn($names);

        return $extension;
    }
}
