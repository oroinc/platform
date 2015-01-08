<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit;

use Oro\Bundle\DistributionBundle\OroKernel;
use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\OroKernelStub;

class OroKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroKernel|OroKernelStub
     */
    protected $kernel;

    protected function setUp()
    {
        $this->kernel = new OroKernelStub('env', false);
    }

    /**
     * @dataProvider bundleList
     */
    public function testCompareBundles($bundles, $expects)
    {
        uasort($bundles, [$this->kernel, 'compareBundles']);
        $id = 0;
        foreach ($bundles as $bundleData) {
            $this->assertEquals($expects[$id], $bundleData['name']);
            $id++;
        }
    }

    public function bundleList()
    {
        return [
            [
                [
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                ],
                [
                    'OroCRMCallBundle',
                    'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                ],
                [
                    'OroCRMCallBundle',
                    'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCallBundle', 'priority' => 30],
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle',
                    'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                    ['name' => 'OroCallBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle',
                    'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                    ['name' => 'OroTestBundle', 'priority' => 30],
                ],
                [
                    'OroTestBundle',
                    'OroCRMCallBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroTestBundle', 'priority' => 30],
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                ],
                [
                    'OroTestBundle',
                    'OroCRMCallBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroSecondBundle', 'priority' => 30],
                    ['name' => 'OroFirstBundle', 'priority' => 20],

                ],
                [
                    'OroFirstBundle',
                    'OroSecondBundle'
                ]
            ],
            [
                [
                    ['name' => 'AcmeLastBundle', 'priority' => 100],
                    ['name' => 'OroCRMAnotherBundle', 'priority' => 30],
                    ['name' => 'AcmeTestBundle', 'priority' => 1],
                    ['name' => 'OroSomeBundle', 'priority' => 30],
                    ['name' => 'AcmeDemoBundle', 'priority' => 100],
                ],
                [
                    'AcmeTestBundle',
                    'OroSomeBundle',
                    'OroCRMAnotherBundle',
                    'AcmeDemoBundle',
                    'AcmeLastBundle',
                ]
            ]
        ];
    }

    /**
     * @param array $bundles
     *
     * @dataProvider bundlesDataProvider
     */
    public function testCollectBundles(array $bundles)
    {
        $this->assertEquals(
            $bundles,
            $this->kernel->registerBundles()
        );
    }

    /**
     * @return array
     */
    public function bundlesDataProvider()
    {
        return [
            [
                [
                    'Acme\Bundle\TestBundle\AcmeSimplifiedBundle'       => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeSimplifiedBundle',
                        'kernel'   => null,
                        'priority' => 0
                    ],
                    'Acme\Bundle\TestBundle\AcmeDuplicateBundle'        => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeDuplicateBundle',
                        'kernel'   => null,
                        'priority' => 50
                    ],
                    'Acme\Bundle\TestBundle\AcmeFirstRegisteredBundle'  => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeFirstRegisteredBundle',
                        'kernel'   => null,
                        'priority' => 50
                    ],
                    'Acme\Bundle\TestBundle\AcmeRegisteredBundle'       => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeRegisteredBundle',
                        'kernel'   => null,
                        'priority' => 50
                    ],
                    'Acme\Bundle\TestBundle\AcmeSecondRegisteredBundle' => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeSecondRegisteredBundle',
                        'kernel'   => null,
                        'priority' => 50
                    ],
                    'Acme\Bundle\TestBundle\AcmeThirdRegisteredBundle'  => [
                        'name'     => 'Acme\Bundle\TestBundle\AcmeThirdRegisteredBundle',
                        'kernel'   => null,
                        'priority' => 50
                    ]
                ],
            ]
        ];
    }

    /**
     * @param string $name
     * @param array $bundles
     * @dataProvider getBundleDataProvider
     */
    public function testGetBundle($name, array $bundles)
    {
        // SingleInheritanceBundle extends NoInheritanceBundle
        // DoubleInheritanceBundle extends SingleInheritanceBundle
        $bundleMap = [
            'NoInheritanceBundle'     => ['DoubleInheritanceBundle', 'SingleInheritanceBundle', 'NoInheritanceBundle'],
            'SingleInheritanceBundle' => ['DoubleInheritanceBundle', 'SingleInheritanceBundle'],
            'DoubleInheritanceBundle' => ['DoubleInheritanceBundle'],
        ];
        $this->kernel->setBundleMap($bundleMap);

        $actualBundles = $this->kernel->getBundle($name, false);
        $this->assertEquals($bundles, $actualBundles);
        $this->assertEquals(current($actualBundles), $this->kernel->getBundle($name, true));
    }

    /**
     * @return array
     */
    public function getBundleDataProvider()
    {
        return [
            'bundle no inheritance' => [
                'name'    => 'NoInheritanceBundle',
                'bundles' => ['DoubleInheritanceBundle', 'SingleInheritanceBundle', 'NoInheritanceBundle'],
            ],
            'bundle single inheritance' => [
                'name'    => 'SingleInheritanceBundle',
                'bundles' => ['DoubleInheritanceBundle', 'SingleInheritanceBundle'],
            ],
            'bundle double inheritance' => [
                'name'    => 'DoubleInheritanceBundle',
                'bundles' => ['DoubleInheritanceBundle'],
            ],
            'precise bundle no inheritance' => [
                'name'    => '!NoInheritanceBundle',
                'bundles' => ['NoInheritanceBundle'],
            ],
            'precise bundle single inheritance' => [
                'name'    => '!SingleInheritanceBundle',
                'bundles' => ['SingleInheritanceBundle'],
            ],
            'precise bundle double inheritance' => [
                'name'    => '!DoubleInheritanceBundle',
                'bundles' => ['DoubleInheritanceBundle'],
            ],
        ];
    }
}
