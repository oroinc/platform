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

        $this->addProviders();

        $this->assertAttributeCount(2, 'providers', $this->chainVirtualFieldProvider);
        $this->assertAttributeEquals($this->providers, 'providers', $this->chainVirtualFieldProvider);
    }

    public function testIsVirtualField()
    {
        $this->addProviders();

        $this->providers[0]
            ->expects($this->at(0))
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));
        $this->providers[0]
            ->expects($this->exactly(2))
            ->method('isVirtualField');
        $this->providers[1]
            ->expects($this->exactly(1))
            ->method('isVirtualField');


        $this->assertTrue($this->chainVirtualFieldProvider->isVirtualField('testClass', 'testField'));
        $this->assertFalse($this->chainVirtualFieldProvider->isVirtualField('testClass', 'testField2'));
    }

    protected function addProviders()
    {
        $provider1 = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('VirtualFieldProvider1')
            ->getMock();

//        $provider1
//            ->expects($this->any())
//            ->method('getVirtualFields')
//            ->with('testClass')
//            ->will($this->returnValue(['testField']));
//
//        $provider1
//            ->expects($this->any())
//            ->method('getVirtualFieldQuery')
//            ->with('testClass', 'testField')
//            ->will(
//                $this->returnValue(
//                    [
//                        'select' => [
//                            'expr' => 'country.name',
//                            'return_type' => 'string'
//                        ],
//                        'join' => [
//                            'left' => [
//                                [
//                                    'join' => 'entity.country',
//                                    'alias' => 'country'
//                                ]
//                            ]
//                        ]
//                    ]
//                )
//            );

        $provider2 = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('VirtualFieldProvider2')
            ->getMock();
//        $provider2
//            ->expects($this->any())
//            ->method('getVirtualFields')
//            ->with('testClass')
//            ->will($this->returnValue(['testField2']));

        $providers = $this->providers = [$provider1, $provider2];
        foreach ($providers as $provider) {
            $this->chainVirtualFieldProvider->addProvider($provider);
        }
    }
}
