<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;

class ChainVirtualFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ChainVirtualFieldProvider */
    protected $chainVirtualFieldProvider;

    /** @var  [VirtualFieldProviderInterface] */
    protected $providers = [];

    protected function setUp()
    {
        $this->chainVirtualFieldProvider = new ChainVirtualFieldProvider();
    }

    public function testAddProviders()
    {
        $this->assertAttributeEquals([], 'providers', $this->chainVirtualFieldProvider);

        $this->assertAttributeEquals($this->providers, 'providers', $this->chainVirtualFieldProvider);
    }

    public function testIsVirtualField()
    {

    }

    protected function addProviders()
    {
        $provider1 = $this->getMock('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface');
        $provider1
            ->expects($this->any())
            ->method('getVirtualFields')
            ->with('testClass')
            ->will($this->returnValue(['testField']));
        $provider1
            ->expects($this->any())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));
        $provider1
            ->expects($this->any())
            ->method('getVirtualFieldQuery')
            ->with('testClass', 'testField')
            ->will(
                $this->returnValue(
                    [
                        'select' => [
                            'expr' => 'country.name',
                            'return_type' => 'string'
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'entity.country',
                                    'alias' => 'country'
                                ]
                            ]
                        ]
                    ]
                )
            );

        $provider2 = $this->getMock('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface');
        $provider2
            ->expects($this->any())
            ->method('getVirtualFields')
            ->with('testClass')
            ->will($this->returnValue([]));

        $providers = $this->providers = [$provider1, $provider2];
        foreach ($providers as $provider) {
            $this->chainVirtualFieldProvider->addProvider($provider);
        }
    }
}
