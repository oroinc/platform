<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\ServiceContainer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer\AppKernelInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Environment\Handler\ContextServiceEnvironmentHandler;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\OroSuiteGenerator;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

class OroTestFrameworkExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $tempDir;

    private array $sharedContexts = [OroMainContext::class];

    protected function setUp(): void
    {
        $this->tempDir = $this->getTempDir('behat');
        mkdir($this->tempDir . '/bundle1/Tests/Behat', 0777, true);
        mkdir($this->tempDir . '/bundle2/Tests/Behat', 0777, true);
    }

    /**
     * @dataProvider processBundleAutoloadProvider
     */
    public function testProcessBundleAutoload(
        array $suiteConfig,
        array $bundlesConfig,
        array $expectedSuiteConfig
    ): void {
        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', $suiteConfig);
        $containerBuilder->setParameter('paths.base', __DIR__);

        $config = [
            'oro_test' => [
                'shared_contexts' => $this->sharedContexts,
            ],
        ];

        $config = $this->processConfig($config);

        $extension = $this->getMockBuilder(OroTestFrameworkExtension::class)
            ->onlyMethods(['hasValidPaths'])
            ->getMock();
        $extension->expects(self::any())
            ->method('hasValidPaths')
            ->willReturn(true);
        $extension->load($containerBuilder, $config);

        $this->updateNelmioServiceDefinitions($containerBuilder);

        $extension->process($containerBuilder);

        self::assertEquals($expectedSuiteConfig, $containerBuilder->getParameter('suite.configurations'));
    }

    public function testLoad(): void
    {
        $containerBuilder = $this->getContainerBuilder([]);
        $sharedContexts = [OroMainContext::class];
        $containerBuilder->setParameter('paths.base', __DIR__);

        $config = [
            'oro_test' => [
                'shared_contexts' => $sharedContexts,
            ],
        ];

        $config = $this->processConfig($config);

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $config);

        self::assertEquals($sharedContexts, $containerBuilder->getParameter('oro_test.shared_contexts'));
    }

    public function testGetConfigKey(): void
    {
        $extension = new OroTestFrameworkExtension();
        self::assertEquals('oro_test', $extension->getConfigKey());
    }

    public function processBundleAutoloadProvider(): array
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
                            'contexts' => [OroMainContext::class],
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
                            'contexts' => [OroMainContext::class],
                            'paths' => ['/var/www/OroBaseBundle/Features'],
                        ],
                    ],
                    'OroExtendBundle' => [
                        'type' => 'symfony_bundle',
                        'settings' => [
                            'contexts' => [OroMainContext::class],
                            'paths' => ['/var/www/OroExtendBundle/Features'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testExceptionForElementsWithSameName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration with "MyElement" key is already defined');

        $config = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement' => [
                        'class' => '\My\Element\MyElement',
                    ],
                ],
            ],
        ];
        $configExtension = [
            'oro_test' => [
                'shared_contexts' => $this->sharedContexts,
            ],
        ];
        file_put_contents($this->tempDir . '/bundle1/Tests/Behat/behat.yml', Yaml::dump($config));
        file_put_contents($this->tempDir . '/bundle2/Tests/Behat/behat.yml', Yaml::dump($config));

        $bundlesConfig = [
            ['name' => 'OroBundle1', 'path' => $this->tempDir . '/bundle1'],
            ['name' => 'OroBundle2', 'path' => $this->tempDir . '/bundle2'],
        ];

        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', []);
        $containerBuilder->setParameter('paths.base', __DIR__);
        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $this->processConfig($configExtension));
        $extension->process($containerBuilder);
    }

    public function testMergeElements(): void
    {
        $config1 = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement1' => [
                        'class' => '\My\Element\MyElement',
                    ],
                ],
            ],
        ];
        $config2 = [
            'oro_behat_extension' => [
                'elements' => [
                    'MyElement2' => [
                        'class' => '\My\Element\MyElement',
                    ],
                ],
            ],
        ];
        $configExtension = [
            'oro_test' => [
                'shared_contexts' => $this->sharedContexts,
            ],
        ];
        file_put_contents($this->tempDir . '/bundle1/Tests/Behat/behat.yml', Yaml::dump($config1));
        file_put_contents($this->tempDir . '/bundle2/Tests/Behat/behat.yml', Yaml::dump($config2));

        $bundlesConfig = [
            ['name' => 'OroBundle1', 'path' => $this->tempDir . '/bundle1'],
            ['name' => 'OroBundle2', 'path' => $this->tempDir . '/bundle2'],
        ];

        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', []);
        $containerBuilder->setParameter('paths.base', __DIR__);
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

    private function getContainerBuilder(array $bundlesConfig): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $kernel = new KernelStub($this->getTempDir('test_kernel_logs'), $bundlesConfig);
        $kernel->getContainer()->set(
            'oro_entity.entity_alias_resolver',
            $this->getMockBuilder(EntityAliasResolver::class)
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->set(
            'oro_security.owner.metadata_provider.chain',
            $this->getMockBuilder(OwnershipMetadataProviderInterface::class)
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->set('logger', new NullLogger());
        $kernel->getContainer()->setParameter('kernel.secret', 'secret');

        $containerBuilder->set('fob_symfony.kernel', $kernel);
        $containerBuilder->set('oro_behat_extension.suite.oro_suite_generator', new OroSuiteGenerator($kernel));
        $containerBuilder->setDefinition('mink.listener.sessions', new Definition());
        $containerBuilder->setDefinition('fob_symfony.kernel_orchestrator', new Definition());

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

    private function processConfig(array $config = []): array
    {
        $tree = new TreeBuilder('oro_test');
        $extension = new OroTestFrameworkExtension();
        $extension->configure($tree->getRootNode());

        $processor = new Processor();

        return $processor->process($tree->buildTree(), $config);
    }

    public function testProcessContextInitializers(): void
    {
        $containerBuilder = $this->getContainerBuilder([]);
        $containerBuilder->setParameter('suite.configurations', []);
        $containerBuilder->setParameter('paths.base', __DIR__);

        $className = $this->getMockClass(ContextInitializer::class);
        $contextInitializerDef = new Definition($this->getMockClass(ContextInitializer::class));
        $contextInitializerDef->addTag(ContextExtension::INITIALIZER_TAG);
        $containerBuilder->setDefinition($className, $contextInitializerDef);

        $envHandlerId = 'oro_test.environment.handler.context_service_environment_handler';
        $envHandlerDef = new Definition(ContextServiceEnvironmentHandler::class);
        $containerBuilder->setDefinition($envHandlerId, $envHandlerDef);

        $config = $this->processConfig(['oro_test' => ['shared_contexts' => $this->sharedContexts]]);

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $config);

        $this->updateNelmioServiceDefinitions($containerBuilder);

        $extension->process($containerBuilder);

        self::assertEquals(
            [
                ['registerContextInitializer', [new Reference($className)]],
                ['registerContextInitializer', [new Reference('oro_behat_page_object_initializer')]],
                ['registerContextInitializer', [new Reference('oro_behat_session_alias_initializer')]],
                ['registerContextInitializer', [new Reference('oro_behat_browser_tab_manager_initializer')]],
                ['registerContextInitializer', [new Reference('oro_behat_fixture_loader_initializer')]],
                ['registerContextInitializer', [new Reference(AppKernelInitializer::class)]],
            ],
            $containerBuilder->getDefinition($envHandlerId)
                ->getMethodCalls()
        );
    }

    public function testProcessResolveClassPass(): void
    {
        $containerBuilder = $this->getContainerBuilder([]);
        $containerBuilder->setParameter('suite.configurations', []);
        $containerBuilder->setParameter('paths.base', __DIR__);

        $sampleServiceDef = new Definition(\stdClass::class);
        $containerBuilder->setDefinition(\stdClass::class, $sampleServiceDef);

        $config = $this->processConfig(['oro_test' => ['shared_contexts' => $this->sharedContexts]]);

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $config);

        $this->updateNelmioServiceDefinitions($containerBuilder);

        $extension->process($containerBuilder);

        self::assertEquals(\stdClass::class, $containerBuilder->getDefinition(\stdClass::class)->getClass());
    }
}
