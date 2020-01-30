<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;
use Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub\SimpleGeneratorStub;

class TagGeneratorChainTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(TagGeneratorChain $generator, $data, $includeCollection, $expectedTags)
    {
        $result = $generator->generate($data, $includeCollection);
        $this->assertSame($expectedTags, $result);
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'Expect one tag from one generator w/o collection'            => [
                new TagGeneratorChain([new SimpleGeneratorStub('s')]),
                'testString',
                false,
                ['testString_s']
            ],
            'Expect two tags from one generator with collection'          => [
                new TagGeneratorChain([new SimpleGeneratorStub('s')]),
                'testString',
                true,
                ['testString_s', 'testString_s_type_collection']
            ],
            'Expect no tags, not supported type, but should return array' => [
                new TagGeneratorChain([new SimpleGeneratorStub('s')]),
                ['someArray'],
                true,
                []
            ],
            'Expected filtration by unique tags'                          => [
                new TagGeneratorChain([new SimpleGeneratorStub('s'), new SimpleGeneratorStub('s')]),
                'testString',
                false,
                ['testString_s']
            ],
            'Expected merge tags from different generators'               => [
                new TagGeneratorChain([
                    new SimpleGeneratorStub('s'),
                    new SimpleGeneratorStub('e'),
                    new SimpleGeneratorStub('s')
                ]),
                'testString',
                false,
                ['testString_s', 'testString_e']
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
        $chain = new TagGeneratorChain($generators);
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
