<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\ServiceContainer;

use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestBundle;
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
     * @param $suiteConfig
     * @param $bundles
     */
    public function testProcessBundleAutoload(array $suiteConfig, array $bundles, array $expectedSuiteConfig)
    {
        $containerBuilder = $this->getContainerBuilder($bundles);
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
                    'OroUserBundle',
                    'OroUIBundle',
                    'OroFormBundle'
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
                'kernel_bundles' => [
                    'OroUserBundle',
                    'OroUIBundle',
                    'OroFormBundle'
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
        ];
    }

    /**
     * @param array $names
     * @return BundleInterface[]
     */
    protected function getBundlesFromNames(array $names)
    {
        $bundles = [];

        foreach ($names as $name) {
            $bundle = new TestBundle($name);

            $bundles[$name] = $bundle;
        }

        return $bundles;
    }

    /**
     * @param array $bundles
     * @return ContainerBuilder
     */
    private function getContainerBuilder(array $bundles)
    {
        $containerBuilder = new ContainerBuilder();

        $kernel = new KernelStub();
        $kernel->setBundleMap($this->getBundlesFromNames($bundles));
        $kernel->getContainer()->set(
            'oro_entity.entity_alias_resolver',
            $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $kernel->getContainer()->set(
            'oro_security.owner.metadata_provider.chain',
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock()
        );

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
