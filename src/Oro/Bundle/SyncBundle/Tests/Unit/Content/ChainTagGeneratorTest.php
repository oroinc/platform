<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Oro\Bundle\SyncBundle\Content\ChainTagGenerator;
use Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub\SimpleGeneratorStub;

class ChainTagGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        ChainTagGenerator $generator,
        mixed $data,
        bool $includeCollection,
        array $expectedTags
    ) {
        $result = $generator->generate($data, $includeCollection);
        $this->assertSame($expectedTags, $result);
    }

    public function generateDataProvider(): array
    {
        return [
            'Expect one tag from one generator w/o collection'            => [
                new ChainTagGenerator([new SimpleGeneratorStub('s')]),
                'testString',
                false,
                ['testString_s']
            ],
            'Expect two tags from one generator with collection'          => [
                new ChainTagGenerator([new SimpleGeneratorStub('s')]),
                'testString',
                true,
                ['testString_s', 'testString_s_type_collection']
            ],
            'Expect no tags, not supported type, but should return array' => [
                new ChainTagGenerator([new SimpleGeneratorStub('s')]),
                ['someArray'],
                true,
                []
            ],
            'Expected filtration by unique tags'                          => [
                new ChainTagGenerator([new SimpleGeneratorStub('s'), new SimpleGeneratorStub('s')]),
                'testString',
                false,
                ['testString_s']
            ],
            'Expected merge tags from different generators'               => [
                new ChainTagGenerator([
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
     */
    public function testSupports(array $generators, ?string $data, bool $result)
    {
        $chain = new ChainTagGenerator($generators);
        $this->assertEquals($result, $chain->supports($data));
    }

    public function supportsDataProvider(): array
    {
        return [
            'should not supports if no one generator given' => [[], null, false],
            'should support if any generator supports data' => [[new SimpleGeneratorStub('s')], 'testString', true],
            'should not support if no generator supported'  => [[new SimpleGeneratorStub('s')], 'someBadString', false],
        ];
    }
}
