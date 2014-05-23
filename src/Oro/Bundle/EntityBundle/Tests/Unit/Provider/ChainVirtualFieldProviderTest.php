<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;

class ChainVirtualFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainVirtualFieldProvider */
    protected $chainProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject[] */
    protected $providers = [];

    protected function setUp()
    {
        $this->chainProvider = new ChainVirtualFieldProvider();

        $highPriorityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('HighPriorityVirtualFieldProvider')
            ->getMock();
        $lowPriorityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->setMockClassName('LowPriorityVirtualFieldProvider')
            ->getMock();

        $this->chainProvider->addProvider($lowPriorityProvider);
        $this->chainProvider->addProvider($highPriorityProvider, -10);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
    }

    public function testIsVirtualFieldByLowPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));
        $this->providers[1]
            ->expects($this->never())
            ->method('isVirtualField');

        $this->assertTrue($this->chainProvider->isVirtualField('testClass', 'testField'));
    }

    public function testIsVirtualFieldByHighPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(true));

        $this->assertTrue($this->chainProvider->isVirtualField('testClass', 'testField'));
    }

    public function testIsVirtualFieldNone()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));
        $this->providers[1]
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('testClass', 'testField')
            ->will($this->returnValue(false));

        $this->assertFalse($this->chainProvider->isVirtualField('testClass', 'testField'));
    }

    public function testGetVirtualFields()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('getVirtualFields')
            ->with('testClass')
            ->will($this->returnValue(['testField1', 'testField2']));
        $this->providers[1]
            ->expects($this->once())
            ->method('getVirtualFields')
            ->with('testClass')
            ->will($this->returnValue(['testField1', 'testField3']));

        $this->assertEquals(
            ['testField1', 'testField2', 'testField3'],
            $this->chainProvider->getVirtualFields('testClass')
        );
    }

    public function testGetVirtualFieldQuery()
    {
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
            $this->chainProvider->getVirtualFieldQuery('testClass0', 'testField0-1')
        );

        $this->assertEquals(
            $fieldsConfig['testClass1']['testField1-1'],
            $this->chainProvider->getVirtualFieldQuery('testClass1', 'testField1-1')
        );

        try {
            $this->chainProvider->getVirtualFieldQuery('testClass1', 'testField1-2');
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
}
