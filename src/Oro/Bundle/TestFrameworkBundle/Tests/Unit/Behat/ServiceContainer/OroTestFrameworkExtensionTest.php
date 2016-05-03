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

    /**
     * @dataProvider processPageObjectsAutoloadProvider
     *
     * @param array $pages
     * @param array $elements
     * @param array $bundles
     * @param array $expectedPages
     * @param array $expectedElements
     */
    public function testProcessPageObjectsAutoload(
        array $pages,
        array $elements,
        array $bundles,
        array $expectedPages,
        array $expectedElements
    ) {
        $containerBuilder = $this->getContainerBuilder($bundles);

        $containerBuilder->setParameter('sensio_labs.page_object_extension.namespaces.element', $elements);
        $containerBuilder->setParameter('suite.configurations', []);
        $config = [
            'shared_contexts' => $this->sharedContexts,
            'elements_namespace_suffix' => '\Tests\Behat\Page\Element',
        ];

        $extension = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension')
            ->setMethods(['hasValidPaths', 'hasDirectory'])
            ->getMock();
        $extension->expects($this->exactly(count($bundles)))->method('hasDirectory')->willReturn(true);
        $extension->load($containerBuilder, $config);
        $extension->process($containerBuilder);

        $this->assertEquals(
            $expectedElements,
            $containerBuilder->getParameter('sensio_labs.page_object_extension.namespaces.element')
        );
    }

    public function testLoad()
    {
        $containerBuilder = new ContainerBuilder();
        $sharedContexts = ['Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext'];
        $pagesNamespaceSuffix = '\Tests\Behat\Page';
        $elementsNamespaceSuffix = '\Tests\Behat\Page\Element';

        $extension = new OroTestFrameworkExtension();
        $extension->load($containerBuilder, [
            'shared_contexts' => $sharedContexts,
            'elements_namespace_suffix' => $elementsNamespaceSuffix,
        ]);

        $this->assertEquals($sharedContexts, $containerBuilder->getParameter('oro_test.shared_contexts'));
        $this->assertEquals(
            $elementsNamespaceSuffix,
            $containerBuilder->getParameter('oro_test.elements_namespace_suffix')
        );
    }

    public function testGetConfigKey()
    {
        $extension = new OroTestFrameworkExtension();
        $this->assertEquals('oro_test', $extension->getConfigKey());
    }

    public function processPageObjectsAutoloadProvider()
    {
        return [
            'without settings' => [
                [],
                [],
                [
                    'OroUserBundle',
                    'OroUIBundle',
                    'OroFormBundle'
                ],
                [
                    'OroUserBundle\Tests\Behat\Page',
                    'OroUIBundle\Tests\Behat\Page',
                    'OroFormBundle\Tests\Behat\Page',
                ],
                [
                    'OroUserBundle\Tests\Behat\Page\Element',
                    'OroUIBundle\Tests\Behat\Page\Element',
                    'OroFormBundle\Tests\Behat\Page\Element',
                ],
            ],
            'with some settings' => [
                [
                    'OroUserBundle\Tests\Behat\Page'
                ],
                [
                    'OroUserBundle\Tests\Behat\Page\Element'
                ],
                [
                    'OroUserBundle',
                    'OroUIBundle',
                    'OroFormBundle'
                ],
                [
                    'OroUserBundle\Tests\Behat\Page',
                    'OroUIBundle\Tests\Behat\Page',
                    'OroFormBundle\Tests\Behat\Page',
                ],
                [
                    'OroUserBundle\Tests\Behat\Page\Element',
                    'OroUIBundle\Tests\Behat\Page\Element',
                    'OroFormBundle\Tests\Behat\Page\Element',
                ],
            ],
        ];
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

        $containerBuilder->setParameter('sensio_labs.page_object_extension.namespaces.page', []);
        $containerBuilder->setParameter('sensio_labs.page_object_extension.namespaces.element', []);
        $containerBuilder->setParameter('oro_test.elements_namespace_suffix', '\Tests\Behat\Page\Element');
        $containerBuilder->set('symfony2_extension.kernel', $kernel);
        $containerBuilder->set('symfony2_extension.suite.generator', new SymfonySuiteGenerator($kernel));

        return $containerBuilder;
    }
}
