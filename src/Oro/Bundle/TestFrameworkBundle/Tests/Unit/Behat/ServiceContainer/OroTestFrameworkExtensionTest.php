<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\ServiceContainer;

use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

class OroTestFrameworkExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $tempDir;

    /** @var array */
    private $sharedContexts = [OroMainContext::class];

    protected function setUp(): void
    {
        $this->tempDir = $this->getTempDir('behat');
        mkdir($this->tempDir . '/bundle1/Tests/Behat', 0777, true);
        mkdir($this->tempDir . '/bundle2/Tests/Behat', 0777, true);
    }

    /**
     * @dataProvider processBundleAutoloadProvider
     */
    public function testProcessBundleAutoload(array $suiteConfig, array $bundlesConfig, array $expectedSuiteConfig)
    {
        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', $suiteConfig);

        $config = ['oro_test' => [
            'shared_contexts' => $this->sharedContexts,
        ]];

        $config = $this->processConfig($config);

        /** @var OroTestFrameworkExtension|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension')
            ->setMethods(['hasValidPaths', 'hasDirectory'])
            ->getMock();
        $extension->expects($this->any())->method('hasValidPaths')->willReturn(true);
        $extension->load($containerBuilder, $config);

        $this->updateNelmioServiceDefinitions($containerBuilder);

        $extension->process($containerBuilder);

        $this->assertEquals($expectedSuiteConfig, $containerBuilder->getParameter('suite.configurations'));
    }

    public function testLoad()
    {
        $containerBuilder = $this->getContainerBuilder([]);
        $sharedContexts = ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'];

        $config = ['oro_test' => [
            'shared_contexts' => $sharedContexts,
        ]];

        $config = $this->processConfig($config);

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $config);

        $this->assertEquals($sharedContexts, $containerBuilder->getParameter('oro_test.shared_contexts'));
    }

    public function testGetConfigKey()
    {
        $extension = new OroTestFrameworkExtension();
        $this->assertEquals('oro_test', $extension->getConfigKey());
    }

    /**
     * @return array
     */
    public function processBundleAutoloadProvider()
    {
        return [
            'All bundle was configured' => [
                'base_suite_config' => [
                    'OroUserBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroUIBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroFormBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                ],
                'kernel_bundles' => [
                    ['name' => 'OroUserBundle'],
                    ['name' => 'OroUIBundle'],
                    ['name' => 'OroFormBundle'],
                ],
                'expected_suite_config' => [
                    'OroUserBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroUIBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroFormBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                ],
            ],
            'OroFormBundle auto configured' => [
                'base_suite_config' => [
                    'OroUserBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroUIBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                ],
                'kernel_bundles_config' => [
                    ['name' => 'OroUserBundle'],
                    ['name' => 'OroUIBundle'],
                    ['name' => 'OroFormBundle'],
                ],
                'expected_suite_config' => [
                    'OroUserBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroUIBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [],
                    ],
                    'OroFormBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [
                            'contexts' => ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'],
                            'paths' => ['/Features'],
                        ],
                    ],
                ],
            ],
            'Extended bundles auto configured' => [
                'base_suite_config' => [],
                'kernel_bundles_config' => [
                    ['name' => 'OroBaseBundle', 'path' => '/var/www/OroBaseBundle'],
                    ['name' => 'OroExtendBundle', 'parent' => 'OroBaseBundle', 'path' => '/var/www/OroExtendBundle'],
                ],
                'expected_suite_config' => [
                    'OroBaseBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [
                            'contexts' => ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'],
                            'paths' => ['/var/www/OroBaseBundle/Features'],
                        ],
                    ],
                    'OroExtendBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [
                            'contexts' => ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'],
                            'paths' => ['/var/www/OroExtendBundle/Features'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testExceptionForElementsWithSameName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration with "MyElement" key is already defined');

        $config = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement' => [
                        'class' => '\My\Element\MyElement'
                    ],
                ],
            ],
        ];
        $configExtension = ['oro_test' => [
            'shared_contexts' => $this->sharedContexts,
        ]];
        file_put_contents($this->tempDir . '/bundle1/Tests/Behat/behat.yml', Yaml::dump($config));
        file_put_contents($this->tempDir . '/bundle2/Tests/Behat/behat.yml', Yaml::dump($config));

        $bundlesConfig = [
            ['name' => 'OroBundle1', 'path' => $this->tempDir . '/bundle1'],
            ['name' => 'OroBundle2', 'path' => $this->tempDir . '/bundle2']
        ];

        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', []);
        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $this->processConfig($configExtension));
        $extension->process($containerBuilder);
    }

    public function testMergeElements()
    {
        $config1 = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement1' => [
                        'class' => '\My\Element\MyElement'
                    ],
                ],
            ],
        ];
        $config2 = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement2' => [
                        'class' => '\My\Element\MyElement'
                    ],
                ],
            ],
        ];
        $configExtension = ['oro_test' => [
            'shared_contexts' => $this->sharedContexts,
        ]];
        file_put_contents($this->tempDir . '/bundle1/Tests/Behat/behat.yml', Yaml::dump($config1));
        file_put_contents($this->tempDir . '/bundle2/Tests/Behat/behat.yml', Yaml::dump($config2));

        $bundlesConfig = [
            ['name' => 'OroBundle1', 'path' => $this->tempDir . '/bundle1'],
            ['name' => 'OroBundle2', 'path' => $this->tempDir . '/bundle2']
        ];

        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', []);
        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $this->processConfig($configExtension));

        $this->updateNelmioServiceDefinitions($containerBuilder);

        $extension->process($containerBuilder);

        $elementFactoryDefinition = $containerBuilder->getDefinition('oro_element_factory');
        $elements = $elementFactoryDefinition->getArgument(2);

        self::assertCount(2, $elements);
        self::assertArrayHasKey('MyElement1', $elements);
        self::assertArrayHasKey('MyElement2', $elements);
    }

    /**
     * @param array $bundlesConfig
     * @return ContainerBuilder
     */
    private function getContainerBuilder(array $bundlesConfig)
    {
        $containerBuilder = new ContainerBuilder();

        $kernel = new KernelStub($this->getTempDir('test_kernel_logs'), $bundlesConfig);
        $kernel->getContainer()->set(
            'oro_entity.entity_alias_resolver',
            $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->set(
            'oro_security.owner.metadata_provider.chain',
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->set('logger', new NullLogger());
        $kernel->getContainer()->set(
            'oro_message_queue.consumption.cache_state',
            $this->getMockBuilder('Oro\Bundle\MessageQueueBundle\Consumption\CacheState')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->setParameter('kernel.secret', 'secret');

        $containerBuilder->set('symfony2_extension.kernel', $kernel);
        $containerBuilder->set('symfony2_extension.suite.generator', new SymfonySuiteGenerator($kernel));
        $containerBuilder->setDefinition('mink.listener.sessions', new Definition());
        $containerBuilder->setDefinition('symfony2_extension.context_initializer.kernel_aware', new Definition());

        return $containerBuilder;
    }

    private function updateNelmioServiceDefinitions(ContainerBuilder $containerBuilder): void
    {
        $nelmioServices = [
            'nelmio_alice.file_parser.registry',
            'nelmio_alice.fixture_builder.denormalizer.flag_parser.registry',
            'nelmio_alice.fixture_builder.denormalizer.fixture.registry_denormalizer',
            'nelmio_alice.generator.resolver.parameter.registry',
            'nelmio_alice.generator.resolver.value.registry',
            'nelmio_alice.generator.instantiator.registry',
            'nelmio_alice.generator.caller.registry',
        ];

        foreach ($nelmioServices as $nelmioService) {
            $containerBuilder->getDefinition($nelmioService)->addArgument([]);
        }

        $containerBuilder->setDefinition('property_accessor', new Definition(PropertyAccessor::class));
        $containerBuilder->setDefinition('file_locator', new Definition(FileLocator::class));
    }

    /**
     * @param array $config
     * @return array
     */
    private function processConfig(array $config = [])
    {
        $tree = new TreeBuilder('oro_test');
        $extension = new OroTestFrameworkExtension();
        $extension->configure($tree->getRootNode());

        $processor = new Processor();

        return $processor->process($tree->buildTree(), $config);
    }
}
