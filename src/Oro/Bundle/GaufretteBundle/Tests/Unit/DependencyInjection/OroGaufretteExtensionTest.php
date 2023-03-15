<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\DependencyInjection;

use Knp\Bundle\GaufretteBundle\DependencyInjection\KnpGaufretteExtension;
use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\LocalConfigurationFactory;
use Oro\Bundle\GaufretteBundle\DependencyInjection\OroGaufretteExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OroGaufretteExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ExtendedContainerBuilder $container;
    private OroGaufretteExtension $extension;

    private const CONFIG = [
        [
            'adapters'    => [
                'first_adapter'  => ['key' => 'value1'],
                'second_adapter' => ['key' => 'value2'],
                'third_adapter'  => ['key' => 'value3']
            ],
            'filesystems' => [
                'first_fs'  => ['adapter' => 'first_adapter', 'alias' => 'first_fs_filesystem'],
                'second_fs' => ['adapter' => 'second_adapter', 'alias' => 'second_fs_filesystem'],
                'third_fs'  => ['adapter' => 'third_adapter', 'alias' => 'third_fs_filesystem']
            ]
        ]
    ];

    protected function setUp(): void
    {
        $this->container = new ExtendedContainerBuilder();
        $this->container->setParameter('kernel.environment', 'prod');
        $this->container->registerExtension(new KnpGaufretteExtension());
        $this->container->setExtensionConfig('knp_gaufrette', self::CONFIG);

        $this->extension = new OroGaufretteExtension();
        $this->extension->addConfigurationFactory(new LocalConfigurationFactory());
    }

    public function testConfigureReadonlyGaufretteProtocolWhenGaufretteProtocolIsNotConfigured()
    {
        $this->extension->load([], $this->container);
        self::assertFalse($this->container->hasParameter('oro_gaufrette.stream_wrapper.readonly_protocol'));
    }

    public function testConfigureReadonlyGaufretteProtocolWhenGaufretteProtocolIsConfigured()
    {
        $this->container->setParameter('knp_gaufrette.stream_wrapper.protocol', 'gaufrette');
        $this->extension->load([], $this->container);
        self::assertEquals(
            'gaufrette-readonly',
            $this->container->getParameter('oro_gaufrette.stream_wrapper.readonly_protocol')
        );
    }

    public function testConfigureReadonlyGaufretteProtocolWhenIsIsSetExplicitlyAndGaufretteProtocolIsNotConfigured()
    {
        $this->extension->load([['stream_wrapper' => ['readonly_protocol' => 'test-protocol']]], $this->container);
        self::assertFalse($this->container->hasParameter('oro_gaufrette.stream_wrapper.readonly_protocol'));
    }

    public function testConfigureReadonlyGaufretteProtocolWhenIsIsSetExplicitlyAndGaufretteProtocolIsConfigured()
    {
        $this->container->setParameter('knp_gaufrette.stream_wrapper.protocol', 'gaufrette');
        $this->extension->load([['stream_wrapper' => ['readonly_protocol' => 'test-protocol']]], $this->container);
        self::assertEquals(
            'test-protocol',
            $this->container->getParameter('oro_gaufrette.stream_wrapper.readonly_protocol')
        );
    }

    public function testPrependWhenNoConfigParameters()
    {
        $this->extension->prepend($this->container);
        self::assertEquals(self::CONFIG, $this->container->getExtensionConfig('knp_gaufrette'));
    }

    public function testPrependForAdapterConfigParameter()
    {
        $this->container->setParameter('gaufrette_adapter.first_adapter', 'local:/test1');
        $this->container->setParameter('gaufrette_adapter.third_adapter', 'local:/test3');

        $expectedConfig = self::CONFIG;
        $expectedConfig[] = [
            'adapters' => [
                'first_adapter' => [
                    'local' => [
                        'directory' => '/test1'
                    ]
                ],
                'third_adapter' => [
                    'local' => [
                        'directory' => '/test3'
                    ]
                ]
            ]
        ];

        $this->extension->prepend($this->container);
        self::assertEquals($expectedConfig, $this->container->getExtensionConfig('knp_gaufrette'));
    }

    public function testPrependForUnknownAdapterConfigParameter()
    {
        $this->container->setParameter('gaufrette_adapter.unknown_adapter', 'local:/test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "gaufrette_adapter.unknown_adapter" parameter name is invalid'
            . ' because the "unknown_adapter" Gaufrette adapter does not exist.'
            . ' Known adapters: first_adapter, second_adapter, third_adapter.'
        );
        $this->extension->prepend($this->container);
    }

    public function testPrependForUnknownAdapterTypeConfigParameter()
    {
        $this->container->setParameter('gaufrette_adapter.first_adapter', 'unknown:/test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Gaufrette configuration string "unknown:/test" is invalid because'
            . ' the adapter type "unknown" does not exist. Known adapter types: local.'
            . "\n"
            . 'Hints:'
            . "\n"
            . '  local - The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".'
        );
        $this->extension->prepend($this->container);
    }

    public function testPrependForFilesystemConfigParameter()
    {
        $this->container->setParameter('gaufrette_filesystem.first_fs', 'local:/test1');
        $this->container->setParameter('gaufrette_filesystem.third_fs', 'local:/test3');

        $expectedConfig = self::CONFIG;
        $expectedConfig[] = [
            'adapters'    => [
                'first_fs' => [
                    'local' => [
                        'directory' => '/test1'
                    ]
                ],
                'third_fs' => [
                    'local' => [
                        'directory' => '/test3'
                    ]
                ]
            ],
            'filesystems' => [
                'first_fs' => [
                    'adapter' => 'first_fs',
                    'alias'   => 'first_fs_filesystem'
                ],
                'third_fs' => [
                    'adapter' => 'third_fs',
                    'alias'   => 'third_fs_filesystem'
                ]
            ]
        ];

        $this->extension->prepend($this->container);
        self::assertEquals($expectedConfig, $this->container->getExtensionConfig('knp_gaufrette'));
    }

    public function testPrependForUnknownFilesystemConfigParameter()
    {
        $this->container->setParameter('gaufrette_filesystem.unknown_fs', 'local:/test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "gaufrette_filesystem.unknown_fs" parameter name is invalid'
            . ' because the "unknown_fs" Gaufrette filesystem does not exist.'
            . ' Known filesystems: first_fs, second_fs, third_fs.'
        );
        $this->extension->prepend($this->container);
    }

    public function testPrependForFilesystemWithUnknownAdapterTypeConfigParameter()
    {
        $this->container->setParameter('gaufrette_filesystem.first_fs', 'unknown:/test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Gaufrette configuration string "unknown:/test" is invalid because'
            . ' the adapter type "unknown" does not exist. Known adapter types: local.'
            . "\n"
            . 'Hints:'
            . "\n"
            . '  local - The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".'
        );
        $this->extension->prepend($this->container);
    }
}
