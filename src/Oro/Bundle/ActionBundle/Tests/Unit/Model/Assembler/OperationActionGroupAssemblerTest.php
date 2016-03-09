<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\OperationActionGroup;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;

class OperationActionGroupAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OperationActionGroupAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new OperationActionGroupAssembler();
    }

    protected function tearDown()
    {
        unset($this->assembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    /**
     * @return array
     */
    public function assembleProvider()
    {
        $argument1 = new OperationActionGroup();
        $argument1->setName('minimum_name');

        $argument2 = new OperationActionGroup();
        $argument2
            ->setName('maximum_name')
            ->setArgumentsMapping(['argument1']);

        return [
            'no data' => [
                [],
                'expected' => [],
            ],

            'minimum data' => [
                [
                    ['name' => 'minimum_name'],
                ],
                'expected' => [$argument1],
            ],

            'maximum data' => [
                [
                    [
                        'name' => 'maximum_name',
                        'arguments_mapping' => ['argument1'],
                    ],
                ],
                'expected' => [$argument2],
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
                'expected' => [
                    $argument2,
                    $argument2,
                ],
            ],
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
