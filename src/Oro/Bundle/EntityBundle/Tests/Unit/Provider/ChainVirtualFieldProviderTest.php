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

    public function testGetVirtualFields()
    {
        $this->addProviders();

        $fieldsConfig = [
            'testClass0' => ['testField1-1', 'testField1-2'],
            'testClass1' => ['testField2-1'],
        ];

        $this->addFieldsMock($fieldsConfig);

        $this->assertEquals(
            $fieldsConfig['testClass0'],
            $this->chainVirtualFieldProvider->getVirtualFields('testClass0')
        );
        $this->assertEquals(
            $fieldsConfig['testClass1'],
            $this->chainVirtualFieldProvider->getVirtualFields('testClass1')
        );
    }

    public function testGetVirtualFieldQuery()
    {
        $this->addProviders();
        $fieldsConfig = [
            'testClass0' => [
                'testField0-1' => [
                    'select' => ['expr' => 'test.name', 'return_type' => 'string'],
                    'join'   => ['left' => [['join' => 'entity.test', 'alias' => 'test']]]
                ],
            ],
            'testClass1' => [
                'testField1-1' => [
                    'select' => ['expr' => 'test.name', 'return_type' => 'string'],
                    'join'   => ['left' => [['join' => 'entity.test', 'alias' => 'test']]]
                ]
            ]
        ];

        $this->addQueryMock($fieldsConfig);

        $this->assertEquals(
            $fieldsConfig['testClass0']['testField0-1'],
            $this->chainVirtualFieldProvider->getVirtualFieldQuery('testClass0', 'testField0-1')
        );

        $this->assertEquals(
            $fieldsConfig['testClass1']['testField1-1'],
            $this->chainVirtualFieldProvider->getVirtualFieldQuery('testClass1', 'testField1-1')
        );

        try {
            $this->chainVirtualFieldProvider->getVirtualFieldQuery('testClass1', 'testField1-2');
            $this->fail("Expected exception not thrown");
        } catch (\Exception $e) {
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(
                'A query for field "testField1-2" in class "testClass1" was not found.',
                $e->getMessage()
            );
        }
    }

    /**
     * Mocks for getVirtualFieldQuery method
     * @param $fieldsConfig
     */
    protected function addQueryMock($fieldsConfig)
    {
        $providers = $this->providers;
        foreach ($providers as $idx => &$provider) {
            $fields = $fieldsConfig['testClass' . $idx];

            $i = 0;
            foreach ($fields as $fieldName => $fieldConfig) {
                $provider
                    ->expects($this->at($i))
                    ->method('isVirtualField')
                    ->with('testClass' . $idx, $fieldName)
                    ->will(
                        $this->returnCallback(
                            function () use ($fieldsConfig, $idx, $fieldName) {
                                return isset($fieldsConfig['testClass' . $idx][$fieldName]);
                            }
                        )
                    );
                $provider
                    ->expects($this->at(++$i))
                    ->method('getVirtualFieldQuery')
                    ->with('testClass' . $idx, $fieldName)
                    ->will($this->returnValue($fieldsConfig['testClass' . $idx][$fieldName]));

                $i++;
            }
        }
    }

    /**
     * Mocks for getVirtualFields method
     * @param $fieldsConfig
     */
    protected function addFieldsMock($fieldsConfig)
    {
        $providers = $this->providers;
        foreach ($providers as $idx => &$provider) {
            $provider
                ->expects($this->at($idx))
                ->method('getVirtualFields')
                ->with('testClass' . $idx)
                ->will($this->returnValue($fieldsConfig['testClass' . $idx]));
        }
    }

    /**
     * Mock providers
     */
    protected function addProviders()
    {
        $provider1 = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('VirtualFieldProvider1')
            ->getMock();

        $provider2 = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('VirtualFieldProvider2')
            ->getMock();

        $providers = $this->providers = [$provider1, $provider2];
        foreach ($providers as $provider) {
            $this->chainVirtualFieldProvider->addProvider($provider);
        }
    }
}
