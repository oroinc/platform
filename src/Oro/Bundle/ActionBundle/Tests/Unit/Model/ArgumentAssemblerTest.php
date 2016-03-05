<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\Argument;
use Oro\Bundle\ActionBundle\Model\ArgumentAssembler;

class ArgumentAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArgumentAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new ArgumentAssembler();
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

        $this->assertEquals(new ArrayCollection($expected), $definitions);
    }

    /**
     * @return array
     */
    public function assembleProvider()
    {
        $argument1 = new Argument();
        $argument1->setName('minimum_name');

        $argument2 = new Argument();
        $argument2
            ->setName('maximum_name')
            ->setType('type1')
            ->setDefault(['default value'])
            ->setRequired(true)
            ->setMessage('Please provide argument');

        return [
            'no data' => [
                [],
                'expected' => [],
            ],

            'minimum data' => [
                [
                    'minimum_name' => [],
                ],
                'expected' => ['minimum_name' => $argument1],
            ],

            'maximum data' => [
                [
                    'maximum_name' => [
                        'type' => 'type1',
                        'default' => ['default value'],
                        'required' => true,
                        'message' => 'Please provide argument',
                    ]
                ],
                'expected' => [
                    'maximum_name' => $argument2,
                ],
            ],
        ];
    }
}
