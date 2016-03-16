<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Operation;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\OperationActionGroup;
use Oro\Bundle\ActionBundle\Model\Operation\ActionGroupsMappingIterator;

use Oro\Component\Action\Model\ContextAccessor;

class ActionGroupsMappingIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionDataAccess()
    {
        $groups = [
            new OperationActionGroup()
        ];

        $data1 = new ActionData([1]);

        $instance = new ActionGroupsMappingIterator($groups, $data1);

        $this->assertEquals($groups, $instance->getArrayCopy());

        $this->assertEquals($data1, $instance->getData());

        $data2 = new ActionData([2]);

        $instance->setData($data2);

        $this->assertNotEquals($data1, $instance->getData());

        $this->assertEquals($data2, $instance->getData());
    }

    public function testIterationNoValues()
    {
        $mockGroup1 = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $mockGroup1->expects($this->once())->method('getName')->willReturn('actionGroupName');
        $mockGroup1->expects($this->once())->method('getParametersMapping')->willReturn([]);

        $instance = new ActionGroupsMappingIterator([$mockGroup1], new ActionData());

        $array = iterator_to_array($instance);

        $expected = [
            new ActionGroupExecutionArgs('actionGroupName', [])
        ];

        $this->assertEquals($expected, $array);
    }

    /**
     * @dataProvider provideIterationValues
     * @param array $opActionGroups
     * @param array $accessorAts
     * @param array $expected
     */
    public function testIterationThroughValues(
        array $opActionGroups,
        array $accessorAts,
        array $expected
    ) {
        /* @var $contextAccessor ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
        $contextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();

        $data = new ActionData();

        foreach ($accessorAts as $at => $acGetValue) {
            $contextAccessor->expects($this->at($at))
                ->method('getValue')
                ->with($data, $acGetValue['pp'])->willReturn($acGetValue['return']);
        }

        $instance = new ActionGroupsMappingIterator($opActionGroups, $data, $contextAccessor);

        $result = iterator_to_array($instance);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function provideIterationValues()
    {
        $mockOpActionGroupValuesOnly = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationActionGroup')
            ->getMock();
        $mockOpActionGroupValuesOnly->expects($this->once())->method('getName')->willReturn('OAGValuesOnly');
        $mockOpActionGroupValuesOnly->expects($this->once())->method('getParametersMapping')->willReturn(
            ['arg1' => 'val1', 'compound arg2' => ['val2', 'val3']]
        );

        $mockOpActionGroupSimplePath = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationActionGroup')
            ->getMock();
        $mockOpActionGroupSimplePath->expects($this->once())->method('getName')->willReturn('OAGSimplePath');
        $mockOpActionGroupSimplePath->expects($this->once())->method('getParametersMapping')->willReturn(
            ['arg1' => $p1 = new PropertyPath('val1path')]
        );

        $mockOpActionGroupDeepPaths = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationActionGroup')
            ->getMock();
        $mockOpActionGroupDeepPaths->expects($this->once())->method('getName')->willReturn('OAGDeepPaths');
        $mockOpActionGroupDeepPaths->expects($this->once())->method('getParametersMapping')->willReturn(
            ['arg1' => ['property' => $p2 = new PropertyPath('val1path'), 'value' => 'val1']]
        );

        return [
            'values only' => [
                [$mockOpActionGroupValuesOnly],
                [],
                [
                    new ActionGroupExecutionArgs(
                        'OAGValuesOnly',
                        ['arg1' => 'val1', 'compound arg2' => ['val2', 'val3']]
                    )
                ]
            ],
            'simple property path' => [
                [$mockOpActionGroupSimplePath],
                [
                    ['pp' => $p1, 'return' => 'val1']
                ],
                [
                    new ActionGroupExecutionArgs(
                        'OAGSimplePath',
                        ['arg1' => 'val1']
                    )
                ]
            ],
            'deep property path and values' => [
                [$mockOpActionGroupDeepPaths],
                [
                    ['pp' => $p2, 'return' => 'valFromPP1']
                ],
                [
                    new ActionGroupExecutionArgs(
                        'OAGDeepPaths',
                        [
                            'arg1' => [
                                'property' => 'valFromPP1',
                                'value' => 'val1'
                            ]
                        ]
                    )
                ]
            ]
        ];
    }
}
