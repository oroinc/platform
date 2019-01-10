<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ChainVirtualFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainVirtualFieldProvider */
    private $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private $providers = [];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    protected function setUp()
    {
        $highPriorityProvider = $this->getMockBuilder(VirtualFieldProviderInterface::class)
            ->setMockClassName('HighPriorityVirtualFieldProvider')
            ->getMock();
        $lowPriorityProvider = $this->getMockBuilder(VirtualFieldProviderInterface::class)
            ->setMockClassName('LowPriorityVirtualFieldProvider')
            ->getMock();

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->chainProvider = new ChainVirtualFieldProvider($this->providers, $this->configProvider);
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

    public function testIsVirtualFieldWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualFieldProvider([], $this->configProvider);
        $this->assertFalse($chainProvider->isVirtualField('testClass', 'testField'));
    }

    public function testGetVirtualFields()
    {
        $entityClass = 'testClass';

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->providers[0]->expects($this->once())
            ->method('getVirtualFields')
            ->with($entityClass)
            ->will($this->returnValue(['testField1', 'testField2']));
        $this->providers[1]->expects($this->once())
            ->method('getVirtualFields')
            ->with($entityClass)
            ->will($this->returnValue(['testField1', 'testField3']));

        $this->assertEquals(
            ['testField1', 'testField2', 'testField3'],
            $this->chainProvider->getVirtualFields($entityClass)
        );
    }

    public function testGetVirtualFieldsForNotAccessibleEntity()
    {
        $entityClass = 'testClass';

        $entityConfig = new Config(
            $this->createMock(ConfigIdInterface::class),
            ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
        );
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($entityConfig);

        $this->providers[0]->expects($this->never())
            ->method('getVirtualFields');
        $this->providers[1]->expects($this->never())
            ->method('getVirtualFields');

        $this->assertSame(
            [],
            $this->chainProvider->getVirtualFields($entityClass)
        );
    }

    public function testGetVirtualFieldsWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualFieldProvider([], $this->configProvider);
        $this->assertSame([], $chainProvider->getVirtualFields('testClass'));
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A query for field "testField" in class "testClass" was not found.
     */
    public function testGetVirtualFieldQueryWithoutChildProviders()
    {
        $chainProvider = new ChainVirtualFieldProvider([], $this->configProvider);
        $chainProvider->getVirtualFieldQuery('testClass', 'testField');
    }
}
