<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Bundle\ActionBundle\Model\Parameter;

class ParameterAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->assembler = new ParameterAssembler();
    }

    /**
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    public function assembleProvider(): array
    {
        $parameter1 = new Parameter('minimum_name');

        $parameter2 = new Parameter('maximum_name');
        $parameter2
            ->setType('type1')
            ->setDefault(['default value'])
            ->setMessage('Please provide parameter');

        return [
            'no data' => [
                [],
                'expected' => [],
            ],

            'minimum data' => [
                [
                    'minimum_name' => [],
                ],
                'expected' => ['minimum_name' => $parameter1],
            ],

            'maximum data' => [
                [
                    'maximum_name' => [
                        'type' => 'type1',
                        'default' => ['default value'],
                        'required' => true,
                        'message' => 'Please provide parameter',
                    ]
                ],
                'expected' => ['maximum_name' => $parameter2],
            ],
        ];
    }
}
