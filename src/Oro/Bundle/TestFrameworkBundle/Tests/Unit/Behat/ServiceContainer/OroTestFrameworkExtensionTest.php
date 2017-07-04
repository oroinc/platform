<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\ServiceContainer;

use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestBundle;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class OroTestFrameworkExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $sharedContexts = ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'];

    /**
     * @dataProvider processBundleAutoloadProvider
     * @param array $suiteConfig
     * @param array $bundlesConfig
     * @param array $expectedSuiteConfig
     */
    public function testProcessBundleAutoload(array $suiteConfig, array $bundlesConfig, array $expectedSuiteConfig)
    {
        $containerBuilder = $this->getContainerBuilder($bundlesConfig);
        $containerBuilder->setParameter('suite.configurations', $suiteConfig);

        $config = ['oro_test' => [
            'shared_contexts' => $this->sharedContexts,
        ]];

        $config = $this->processConfig($config);

        $extension = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension')
            ->setMethods(['hasValidPaths', 'hasDirectory'])
            ->getMock();
        $extension->expects($this->any())->method('hasValidPaths')->willReturn(true);
        $extension->load($containerBuilder, $config);
        $extension->process($containerBuilder);

        $this->assertEquals($expectedSuiteConfig, $containerBuilder->getParameter('suite.configurations'));
    }

    public function testLoad()
    {
        $containerBuilder = $this->getContainerBuilder([]);
        $sharedContexts = ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'];
        $applicableSuites = ['OroUserBundle'];

        $config = ['oro_test' => [
            'shared_contexts' => $sharedContexts,
            'application_suites' => $applicableSuites,
        ]];

        $config = $this->processConfig($config);

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, $config);

        $this->assertEquals($sharedContexts, $containerBuilder->getParameter('oro_test.shared_contexts'));
        $this->assertEquals($applicableSuites, $containerBuilder->getParameter('oro_test.application_suites'));
    }

    public function testGetConfigKey()
    {
        $extension = new OroTestFrameworkExtension();
        $this->assertEquals('oro_test', $extension->getConfigKey());
    }

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

    /**
     * @param array $bundlesConfig
     * @return ContainerBuilder
     */
    private function getContainerBuilder(array $bundlesConfig)
    {
        $containerBuilder = new ContainerBuilder();

        $kernel = new KernelStub($bundlesConfig);
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

        $containerBuilder->set('symfony2_extension.kernel', $kernel);
        $containerBuilder->set('symfony2_extension.suite.generator', new SymfonySuiteGenerator($kernel));
        $containerBuilder->setDefinition('mink.listener.sessions', new Definition());
        $containerBuilder->setDefinition('symfony2_extension.context_initializer.kernel_aware', new Definition());

        return $containerBuilder;
    }

    /**
     * @return array
     */
    private function processConfig(array $config = [])
    {
        $tree = new TreeBuilder();
        $extension = new OroTestFrameworkExtension();
        $root = $tree->root('oro_test');
        $extension->configure($root);

        $processor = new Processor();

        return $processor->process($tree->buildTree(), $config);
    }
}
