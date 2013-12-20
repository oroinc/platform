<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Content;

use Oro\Bundle\NavigationBundle\Content\TagGeneratorContext;
use Oro\Bundle\NavigationBundle\Tests\Unit\Content\Stub\SimpleGeneratorStub;

class TagGeneratorContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorDataProvider
     *
     * @param array       $generators
     * @param bool|string $exceptionExpected
     */
    public function testConstructor(array $generators, $exceptionExpected = false)
    {
        if ($exceptionExpected) {
            $this->setExpectedException($exceptionExpected);
        }
        new TagGeneratorContext($generators);
    }

    /**
     * @return array
     */
    public function constructorDataProvider()
    {
        return [
            'should throw exception if not TagGeneratorInterface given' => [[$this], '\LogicException'],
            'should not throw exception correct generator given'        => [[new SimpleGeneratorStub('asd')]],
        ];
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param TagGeneratorContext $generator
     * @param mixed               $data
     * @param bool                $includeCollection
     * @param int                 $expectedCount
     */
    public function testGenerate(TagGeneratorContext $generator, $data, $includeCollection, $expectedCount)
    {
        $result = $generator->generate($data, $includeCollection);
        $this->assertCount($expectedCount, $result);

        $this->assertInternalType('array', $result, 'Should always return array');
    }

    public function generateDataProvider()
    {
        return [
            'Expect one tag from one generator w/o collection'            => [
                new TagGeneratorContext([new SimpleGeneratorStub('s')]),
                'testString',
                false,
                1
            ],
            'Expect two tags from one generator with collection'          => [
                new TagGeneratorContext([new SimpleGeneratorStub('s')]),
                'testString',
                true,
                2
            ],
            'Expect no tags, not supported type, but should return array' => [
                new TagGeneratorContext([new SimpleGeneratorStub('s')]),
                ['someArray'],
                true,
                0
            ],
            'Expected filtration by unique tags'                          => [
                new TagGeneratorContext([new SimpleGeneratorStub('s'), new SimpleGeneratorStub('s')]),
                'testString',
                false,
                1
            ],
            'Expected merge tags from different generators'               => [
                new TagGeneratorContext([new SimpleGeneratorStub('s'), new SimpleGeneratorStub('e')]),
                'testString',
                false,
                2
            ],
        ];
    }
}
