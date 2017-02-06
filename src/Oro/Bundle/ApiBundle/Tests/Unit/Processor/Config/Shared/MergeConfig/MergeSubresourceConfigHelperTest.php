<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeFilterConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeSubresourceConfigHelper;

class MergeSubresourceConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mergeActionConfigHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mergeFilterConfigHelper;

    /** @var MergeSubresourceConfigHelper */
    protected $mergeSubresourceConfigHelper;

    protected function setUp()
    {
        $this->mergeActionConfigHelper = $this->getMockBuilder(MergeActionConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergeFilterConfigHelper = $this->getMockBuilder(MergeFilterConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mergeSubresourceConfigHelper = new MergeSubresourceConfigHelper(
            $this->mergeActionConfigHelper,
            $this->mergeFilterConfigHelper
        );
    }

    public function testMergeEmptySubresourceConfig()
    {
        $config = [];
        $subresourceConfig = [];

        $this->mergeActionConfigHelper->expects(self::never())
            ->method('mergeActionConfig');
        $this->mergeFilterConfigHelper->expects(self::never())
            ->method('mergeFiltersConfig');

        self::assertEquals(
            [],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'action1',
                true,
                true
            )
        );
    }

    public function testMergeSubresourceActionConfig()
    {
        $config = [
            'key' => 'val'
        ];
        $subresourceConfig = [
            'actions' => [
                'action1' => [
                    'description' => 'action 1'
                ]
            ],
            'filters' => [
                'filter1' => [
                    'description' => 'filter 1'
                ]
            ]
        ];

        $this->mergeActionConfigHelper->expects(self::once())
            ->method('mergeActionConfig')
            ->with($config, $subresourceConfig['actions']['action1'], true)
            ->willReturn(
                [
                    'key'         => 'val',
                    'description' => 'merged action 1'
                ]
            );
        $this->mergeFilterConfigHelper->expects(self::never())
            ->method('mergeFiltersConfig');

        self::assertEquals(
            [
                'key'         => 'val',
                'description' => 'merged action 1'
            ],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'action1',
                true,
                false
            )
        );
    }

    public function testMergeSubresourceFiltersConfig()
    {
        $config = [
            'key' => 'val'
        ];
        $subresourceConfig = [
            'actions' => [
                'action1' => [
                    'description' => 'action 1'
                ]
            ],
            'filters' => [
                'filter1' => [
                    'description' => 'filter 1'
                ]
            ]
        ];

        $this->mergeActionConfigHelper->expects(self::never())
            ->method('mergeActionConfig');
        $this->mergeFilterConfigHelper->expects(self::once())
            ->method('mergeFiltersConfig')
            ->with($config, $subresourceConfig['filters'])
            ->willReturn(
                [
                    'key'     => 'val',
                    'filters' => [
                        'filter1' => [
                            'description' => 'merged filter 1'
                        ]
                    ]
                ]
            );

        self::assertEquals(
            [
                'key'     => 'val',
                'filters' => [
                    'filter1' => [
                        'description' => 'merged filter 1'
                    ]
                ]
            ],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'anotherAction',
                false,
                true
            )
        );
    }
}
