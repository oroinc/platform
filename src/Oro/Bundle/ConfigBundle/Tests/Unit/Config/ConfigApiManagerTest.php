<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;

class ConfigApiManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigApiManager */
    protected $manager;

    protected function setUp()
    {
        $this->configProvider = $this->getMock('Oro\Bundle\ConfigBundle\Provider\ProviderInterface');
        $this->configManager  = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ConfigApiManager($this->configProvider, $this->configManager);
    }

    public function testGetSections()
    {
        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with(null)
            ->will(
                $this->returnValue(
                    [
                        'test_section' => [
                            'item1' => 'acme.item1',
                            'foo'   => [
                                'item2' => 'acme.item2'
                            ],
                            'bar'   => [
                                'item2' => 'acme.item2'
                            ],
                        ],
                        'section2'     => [
                            'item3' => 'acme.item3',
                        ]
                    ]
                )
            );
        $this->assertSame(
            [
                'section2',
                'test_section',
                'test_section/bar',
                'test_section/foo',
            ],
            $this->manager->getSections()
        );
    }

    public function testGetData()
    {
        $path = 'section1/section11';

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->will(
                $this->returnValue(
                    [
                        'item1'        => 'acme.item1',
                        'sub_section1' => [
                            'item2'         => 'acme.item2',
                            'sub_section11' => [
                                'item3' => 'acme.item3'
                            ]
                        ]
                    ]
                )
            );
        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['acme.item1', false, false, 'val1'],
                        ['acme.item2', false, false, 123],
                        ['acme.item3', false, false, ['val1' => 1, 'val2' => true]],
                    ]
                )
            );

        $this->assertSame(
            [
                'item1'        => 'val1',
                'sub_section1' => [
                    'item2'         => 123,
                    'sub_section11' => [
                        'item3' => ['val1' => 1, 'val2' => true]
                    ]
                ]
            ],
            $this->manager->getData($path)
        );
    }
}
