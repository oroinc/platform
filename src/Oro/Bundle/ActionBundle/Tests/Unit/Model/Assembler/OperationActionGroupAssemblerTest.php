<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\OperationActionGroup;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class OperationActionGroupAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OperationActionGroupAssembler */
    protected $assembler;

    /** @var ConfigurationPassInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockConfigurationPass;

    protected function setUp()
    {
        $this->mockConfigurationPass = $this->getMockBuilder(
            'Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface'
        )->getMock();

        $this->assembler = new OperationActionGroupAssembler($this->mockConfigurationPass);
    }

    protected function tearDown()
    {
        unset($this->assembler);
    }

    /**
     * @param array $configuration
     * @param array $passes
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $passes, array $expected)
    {
        foreach ($passes as $at => $pass) {
            $this->mockConfigurationPass->expects($this->at($at))
                ->method('passConfiguration')
                ->willReturn($pass);
        }

        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    /**
     * @return array
     */
    public function assembleProvider()
    {
        $actionGroup1 = new OperationActionGroup();
        $actionGroup1->setName('minimum_name');

        $actionGroup2 = new OperationActionGroup();
        $actionGroup2
            ->setName('maximum_name')
            ->setArgumentsMapping(['argument1']);

        $actionGroup3 = new OperationActionGroup();
        $actionGroup3
            ->setName('with property path mapping')
            ->setArgumentsMapping([new PropertyPath('propertyPath')]);

        return [
            'no data' => [
                [],
                'passes' => [],
                'expected' => [],
            ],

            'minimum data' => [
                [
                    ['name' => 'minimum_name'],
                ],
                'passes' => [[]],
                'expected' => [$actionGroup1],
            ],

            'maximum data' => [
                [
                    [
                        'name' => 'maximum_name',
                        'arguments_mapping' => ['argument1'],
                    ],
                ],
                'passes' => [['argument1']],
                'expected' => [$actionGroup2],
            ],
            'repeat items' => [
                [
                    [
                        'name' => 'maximum_name',
                        'arguments_mapping' => ['argument1'],
                    ],
                    [
                        'name' => 'maximum_name',
                        'arguments_mapping' => ['argument1'],
                    ],
                ],
                'passes' => [['argument1'], ['argument1']],
                'expected' => [
                    $actionGroup2,
                    $actionGroup2,
                ],
            ],
            'with property pass' => [
                [
                    [
                        'name' => 'with property path mapping',
                        'arguments_mapping' => ['$.propertyPath']
                    ]
                ],
                'passes' => [[new PropertyPath('propertyPath')]],
                'expected' => [
                    $actionGroup3
                ]
            ]
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException
     * @expectedExceptionMessage Option "name" is required
     */
    public function testAssembleWithMissedRequiredOptions()
    {
        $configuration = [
            'test_config' => [],
        ];

        $this->assembler->assemble($configuration);
    }
}
