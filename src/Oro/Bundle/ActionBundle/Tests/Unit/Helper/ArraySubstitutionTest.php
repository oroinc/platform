<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Oro\Bundle\ActionBundle\Helper\ArraySubstitution;

class ArraySubstitutionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider applyProvider
     */
    public function testApply(array $map, array $things, array $expected)
    {
        $substitution = new ArraySubstitution();
        $substitution->setMap($map);

        $substitution->apply($things);

        $this->assertEquals($expected, $things);
    }

    public function applyProvider(): array
    {
        return [
            'simple' => [
                [
                    'target' => 'replacement'
                ],
                [
                    'target' => ['targetOperationBody'],
                    'replacement' => ['replacementActionBody']
                ],
                [
                    'target' => ['replacementActionBody']
                ]
            ],
            'deep' => [
                [
                    'target' => 'replacement1',
                    'replacement1' => 'replacement2',
                    'replacement2' => 'replacement3',
                    'replacement3' => 'replacement4'
                ],
                [
                    'target' => ['targetActionBody'],
                    'replacement1' => ['replacement1ActionBody'],
                    'replacement2' => ['replacement2ActionBody'],
                    'replacement3' => ['replacement3ActionBody'],
                    'replacement4' => ['replacement4ActionBody']
                ],
                [
                    'target' => ['replacement4ActionBody']
                ]
            ],
            'deep broken by context' => [
                [
                    'target' => 'replacement1',
                    'replacement1' => 'replacement2',
                    'replacement2' => 'replacement3',
                    'replacement3' => 'replacement4'
                ],
                [
                    'target' => ['targetActionBody'],
                    'replacement1' => ['replacement1ActionBody'],
                    'replacement2' => ['replacement2ActionBody'],
                    'replacement4' => ['replacement4ActionBody']
                ],
                [
                    'target' => ['replacement2ActionBody']
                ]
            ],
            'deep broken by substitutions will cause to deal with replacement3 as with normal target' => [
                [
                    'target' => 'replacement1',
                    'replacement1' => 'replacement2',
                    'replacement3' => 'replacement4'
                ],
                [
                    'target' => ['targetActionBody'],
                    'replacement1' => ['replacement1ActionBody'],
                    'replacement2' => ['replacement2ActionBody'],
                    'replacement3' => ['replacement3ActionBody'],
                    'replacement4' => ['replacement4ActionBody']
                ],
                [
                    'target' => ['replacement2ActionBody'],
                    'replacement3' => ['replacement4ActionBody']
                ]
            ],
            'no targets' => [
                [
                    'target' => 'replacement'
                ],
                [
                    'action' => ['actionBody'],
                    'replacement' => ['replacement']
                ],
                [
                    'action' => ['actionBody']
                ]
            ],
            'no substitutions' => [
                [
                    'target' => 'replacement',
                ],
                [
                    'target' => ['targetActionBody'],
                ],
                [
                    'target' => ['targetActionBody'],
                ]
            ],
        ];
    }

    public function testMaxDepthException()
    {
        $substitution = new ArraySubstitution(true, 2);
        $substitution->setMap([
            'a' => 'c',
            'c' => 'b',
            'b' => 'e'
        ]);

        $this->expectException(CircularReferenceException::class);

        $things = [
            'a' => ['a body'],
            'c' => ['c body'],
            'b' => ['b body'],
            'e' => ['e body']
        ];

        $substitution->apply($things);
    }

    /**
     * @dataProvider clearUnboundProvider
     */
    public function testNotClearUnbound(array $map, array $things, array $expected)
    {
        $arraySubstitution = new ArraySubstitution(false);
        $arraySubstitution->setMap($map);

        $applyTo = $things;

        $arraySubstitution->apply($applyTo);

        $this->assertEquals($expected, $applyTo);
    }

    public function clearUnboundProvider(): array
    {
        return [
            'simple' => [
                [
                    'target' => 'replacement',
                    'unknownTarget' => 'someOtherReplacement'
                ],
                [
                    'target' => ['targetActionBody'],
                    'replacement' => ['replacementActionBody'],
                    'someOtherReplacement' => ['matched replacement']
                ],
                [
                    'target' => ['replacementActionBody'],
                    'someOtherReplacement' => ['matched replacement']
                ]
            ],
            'deep with broken' => [
                [
                    'target' => 'replacement1',
                    'replacement1' => 'replacement2',
                    'replacement2' => 'replacement3',
                    'replacement3' => 'replacement4',
                    'replacement5' => 'replacement6'
                ],
                [
                    'target' => ['targetActionBody'],
                    'replacement1' => ['replacement1ActionBody'],
                    'replacement2' => ['replacement2ActionBody'],
                    'replacement3' => ['replacement3ActionBody'],
                    'replacement6' => ['replacement4ActionBody']
                ],
                [
                    'target' => ['replacement3ActionBody'],
                    'replacement6' => ['replacement4ActionBody'],
                    'replacement1' => ['replacement1ActionBody'],
                    'replacement2' => ['replacement2ActionBody'],
                ]
            ]
        ];
    }
}
