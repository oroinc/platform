<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\ServiceContainer;

use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        $containerBuilder->get('symfony2_extension.kernel')->getContainer()->setParameter(
            'database_driver',
            'pdo_mysql'
        );
        $config = [
            'shared_contexts' => $this->sharedContexts,
            'elements_namespace_suffix' => '\Tests\Behat\Page\Element',
        ];

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
        $containerBuilder = new ContainerBuilder();
        $sharedContexts = ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'];

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, [
            'shared_contexts' => $sharedContexts,
        ]);

        $this->assertEquals($sharedContexts, $containerBuilder->getParameter('oro_test.shared_contexts'));
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

        $containerBuilder->set('symfony2_extension.kernel', $kernel);
        $containerBuilder->set('symfony2_extension.suite.generator', new SymfonySuiteGenerator($kernel));

        return $containerBuilder;
    }
}
