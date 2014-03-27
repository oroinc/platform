<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit;

use Oro\Bundle\DistributionBundle\OroKernel;

class OroKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroKernel
     */
    protected $kernel;

    public function setUp()
    {
        $this->kernel = $this->getMockForAbstractClass(
            'Oro\Bundle\DistributionBundle\OroKernel',
            [],
            '',
            false
        );
    }

    /**
     * @dataProvider bundleList
     */
    public function testCompareBundles($bundles, $expects)
    {
        uasort($bundles, array($this->kernel, 'compareBundles'));
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
                    'OroCRMCallBundle', 'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                ],
                [
                    'OroCRMCallBundle', 'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCallBundle', 'priority' => 30],
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle', 'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMTestBundle', 'priority' => 30],
                    ['name' => 'OroCallBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle', 'OroCRMTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                    ['name' => 'OroTestBundle', 'priority' => 30],
                ],
                [
                    'OroTestBundle', 'OroCRMCallBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroTestBundle', 'priority' => 30],
                    ['name' => 'OroCRMCallBundle', 'priority' => 30],
                ],
                [
                    'OroTestBundle', 'OroCRMCallBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroSecondBundle', 'priority' => 30],
                    ['name' => 'OroFirstBundle', 'priority' => 20],

                ],
                [
                    'OroFirstBundle', 'OroSecondBundle'
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
                    'AcmeTestBundle', 'OroSomeBundle', 'OroCRMAnotherBundle', 'AcmeDemoBundle', 'AcmeLastBundle',
                ]
            ]
        ];
    }
}
