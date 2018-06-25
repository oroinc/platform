<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;
use Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub\SimpleGeneratorStub;

class TagGeneratorChainTest extends \PHPUnit\Framework\TestCase
{
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
     * @param TagGeneratorChain $generator
     * @param mixed             $data
     * @param bool              $includeCollection
     * @param int               $expectedCount
     */
    public function testGenerate(TagGeneratorChain $generator, $data, $includeCollection, $expectedCount)
    {
        $result = $generator->generate($data, $includeCollection);
        $this->assertCount($expectedCount, $result);

        $this->assertInternalType('array', $result, 'Should always return array');
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'Expect one tag from one generator w/o collection'            => [
                (new TagGeneratorChain())->addGenerator(new SimpleGeneratorStub('s')),
                'testString',
                false,
                1
            ],
            'Expect two tags from one generator with collection'          => [
                (new TagGeneratorChain())->addGenerator(new SimpleGeneratorStub('s')),
                'testString',
                true,
                2
            ],
            'Expect no tags, not supported type, but should return array' => [
                (new TagGeneratorChain())->addGenerator(new SimpleGeneratorStub('s')),
                ['someArray'],
                true,
                0
            ],
            'Expected filtration by unique tags'                          => [
                (new TagGeneratorChain())
                    ->addGenerator(new SimpleGeneratorStub('s'))
                    ->addGenerator(new SimpleGeneratorStub('s')),
                'testString',
                false,
                1
            ],
            'Expected merge tags from different generators'               => [
                (new TagGeneratorChain())
                    ->addGenerator(new SimpleGeneratorStub('s'))
                    ->addGenerator(new SimpleGeneratorStub('e')),
                'testString',
                false,
                2
            ],
        ];
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param array $generators
     * @param mixed $data
     * @param bool  $result
     */
    public function testSupports(array $generators, $data, $result)
    {
        $chain = new TagGeneratorChain();
        foreach ($generators as $generator) {
            $chain->addGenerator($generator);
        }
        $this->assertEquals($result, $chain->supports($data));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'should not supports if no one generator given' => [[], null, false],
            'should support if any generator supports data' => [[new SimpleGeneratorStub('s')], 'testString', true],
            'should not support if no generator supported'  => [[new SimpleGeneratorStub('s')], 'someBadString', false],
        ];
    }
}
