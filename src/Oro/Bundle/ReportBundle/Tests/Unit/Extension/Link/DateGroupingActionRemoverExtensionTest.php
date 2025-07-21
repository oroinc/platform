<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Extension\Link;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ReportBundle\Extension\Link\DateGroupingActionRemoverExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateGroupingActionRemoverExtensionTest extends TestCase
{
    private DatagridConfiguration&MockObject $config;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = $this->createMock(DatagridConfiguration::class);
    }

    public function testIsApplicableFalse(): void
    {
        $this->config->expects($this->once())
            ->method('offsetExists')
            ->willReturn(false);
        $extension = new DateGroupingActionRemoverExtension('');
        $extension->setParameters(new ParameterBag());

        $this->assertFalse($extension->isApplicable($this->config));
    }

    public function testIsApplicableFalseIfNotCorrectTable(): void
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

    public function testIsApplicableTrue(): void
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
     * @dataProvider visitResultProvider
     */
    public function testVisitResult($inputRows, $expectedRows): void
    {
        $result = $this->createMock(ResultsObject::class);
        $result->expects($this->once())
            ->method('getData')
            ->willReturn($inputRows);
        $result->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($newRows) use ($expectedRows) {
                $this->assertSame($expectedRows, $newRows);
            });

        $extension = new DateGroupingActionRemoverExtension('');
        $extension->setParameters(new ParameterBag());

        $extension->visitResult($this->config, $result);
    }

    public function visitResultProvider(): array
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
