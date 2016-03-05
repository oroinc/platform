<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\SubstitutionVenue;

class SubstitutionVenueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $map
     * @param array $things
     * @param array $expected
     * @dataProvider applyProvider
     */
    public function testApply(array $map, array $things, array $expected)
    {
        $substotutor = new SubstitutionVenue();

        $substotutor->setMap($map);

        $substotutor->apply($things);

        $this->assertEquals($expected, $things);
    }

    /**
     * @return array
     */
    public function applyProvider()
    {
        return [
            'simple' => [
                [
                    'target' => 'replacement'
                ],
                [
                    'target' => ['targetActionBody'],
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
        $substitutor = new SubstitutionVenue(true, 2);

        $substitutor->setMap([
            'a' => 'c',
            'c' => 'b',
            'b' => 'e'
        ]);

        $this->setExpectedException('Oro\Bundle\ActionBundle\Exception\CircularReferenceException');

        $substitutor->substitute([
            'a' => ['a body'],
            'c' => ['c body'],
            'b' => ['b body'],
            'e' => ['e body']
        ]);
    }

    /**
     * @dataProvider clearUnboundProvider
     * @param $map
     * @param $things
     * @param $expected
     */
    public function testNotClearUnbound($map, $things, $expected)
    {
        $substitutionVenue = new SubstitutionVenue(false);
        $substitutionVenue->setMap($map);

        $this->assertEquals($expected, $substitutionVenue->substitute($things));
    }

    public function clearUnboundProvider()
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
