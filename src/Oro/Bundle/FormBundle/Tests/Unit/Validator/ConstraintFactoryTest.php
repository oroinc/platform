<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConstraintFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $expected
     * @param string $name
     * @param mixed  $options
     *
     * @dataProvider createDataProvider
     */
    public function testCreate($expected, $name, $options)
    {
        $factory = new ConstraintFactory();
        $this->assertEquals($expected, $factory->create($name, $options));
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'short name'        => [
                'expectedClass' => new NotBlank(),
                'name'          => 'NotBlank',
                'options'       => null,
            ],
            'custom class name' => [
                'expectedClass' => new Length(['min' => 2, 'max' => 255]),
                'name'          => 'Symfony\Component\Validator\Constraints\Length',
                'options'       => ['min' => 2, 'max' => 255],
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateInvalidConstraint()
    {
        $factory = new ConstraintFactory();
        $factory->create('test');
    }

    /**
     * @param array $constraints
     * @param array $expected
     *
     * @dataProvider constraintsProvider
     */
    public function testParse($constraints, $expected)
    {
        $factory = new ConstraintFactory();
        $this->assertEquals($expected, $factory->parse($constraints));
    }

    /**
     * @return array
     */
    public function constraintsProvider()
    {
        return [
            'empty'              => [
                'constraints' => [],
                'expected'    => []
            ],
            'constraint object'  => [
                'constraints' => [
                    new NotBlank()
                ],
                'expected'    => [
                    new NotBlank()
                ]
            ],
            'by name'            => [
                'constraints' => [
                    [
                        'NotBlank' => null
                    ]
                ],
                'expected'    => [
                    new NotBlank()
                ]
            ],
            'by full class name' => [
                'constraints' => [
                    [
                        'Symfony\Component\Validator\Constraints\Length' => [
                            'min' => 1,
                            'max' => 2,
                        ]
                    ]
                ],
                'expected'    => [
                    new Length(['min' => 1, 'max' => 2])
                ]
            ],
        ];
    }

    /**
     * @param array $constraints
     *
     * @dataProvider invalidConstraintsProvider
     * @expectedException \InvalidArgumentException
     */
    public function testParseWithInvalidArgument($constraints)
    {
        $factory = new ConstraintFactory();
        $factory->parse($constraints);
    }

    /**
     * @return array
     */
    public function invalidConstraintsProvider()
    {
        return [
            [
                'constraints' => [
                    'test'
                ]
            ],
            [
                'constraints' => [
                    ['test' => null]
                ]
            ],
            [
                'constraints' => [
                    ['Test\UndefinedClass' => null]
                ]
            ],
            [
                'constraints' => [
                    new \stdClass()
                ]
            ],
        ];
    }
}
