<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Link;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ReportBundle\Extension\Link\DateGroupingActionRemoverExtension;

class DateGroupingActionRemoverExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsApplicableFalse()
    {
        $this->config->expects($this->once())
            ->method('offsetExists')
            ->willReturn(false);
        $extension = new DateGroupingActionRemoverExtension('');
        $extension->setParameters(new ParameterBag());

        $this->assertFalse($extension->isApplicable($this->config));
    }

    public function testIsApplicableFalseIfNotCorrectTable()
    {
        $this->config->expects($this->once())
            ->method('offsetExists')
            ->willReturn(true);
        $this->config->expects($this->exactly(2))
            ->method('offsetGet')
            ->willReturn(
                [
                    'query' => ['from' => [0 => ['table' => 'none']]],
                ]
            );
        $extension = new DateGroupingActionRemoverExtension('');
        $extension->setParameters(new ParameterBag());

        $this->assertFalse($extension->isApplicable($this->config));
    }


    public function testIsApplicableTrue()
    {
        $this->config->expects($this->once())
            ->method('offsetExists')
            ->willReturn(true);
        $this->config->expects($this->exactly(2))
            ->method('offsetGet')
            ->willReturn(
                [
                    'query' => ['from' => [0 => ['table' => 'correctTable']]],
                ]
            );
        $extension = new DateGroupingActionRemoverExtension('correctTable');
        $extension->setParameters(new ParameterBag());

        $this->assertTrue($extension->isApplicable($this->config));
    }

    /**
     * @param        [] $inputRows
     * @param        [] $expectedRows
     * @dataProvider visitResultProvider
     */
    public function testVisitResult($inputRows, $expectedRows)
    {
        /** @var ResultsObject|\PHPUnit\Framework\MockObject\MockObject $result * */
        $result = $this->getMockBuilder(ResultsObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result->expects($this->once())
            ->method('getData')
            ->willReturn($inputRows);
        $result->expects($this->once())
            ->method('setData')
            ->will(
                $this->returnCallback(
                    function ($newRows) use ($expectedRows) {
                        $this->assertSame($expectedRows, $newRows);
                    }
                )
            );

        $extension = new DateGroupingActionRemoverExtension('');
        $extension->setParameters(new ParameterBag());

        $extension->visitResult($this->config, $result);
    }

    /**
     * @return array
     */
    public function visitResultProvider()
    {
        return [
            [
                [
                    [
                        'action_configuration' => [
                            'view' => true,
                            'update' => true,
                            'delete' => true,
                        ],
                    ],
                ],
                [
                    [
                        'action_configuration' => [
                            'view' => false,
                            'update' => false,
                            'delete' => false,
                        ],
                    ],
                ],
            ],
            [
                [
                    [
                        'action_configuration' => [
                        ],
                    ],
                ],
                [
                    [
                        'action_configuration' => [
                            'view' => false,
                            'update' => false,
                            'delete' => false,
                        ],
                    ],
                ],
            ],
            [
                [
                    [
                    ],
                ],
                [
                    [
                        'action_configuration' => [
                            'view' => false,
                            'update' => false,
                            'delete' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
