<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Generator;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;

class UniversalCacheKeyGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectCacheKeyGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $objectCacheKeyGenerator;

    /** @var UniversalCacheKeyGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->objectCacheKeyGenerator = $this->createMock(ObjectCacheKeyGenerator::class);

        $this->generator = new UniversalCacheKeyGenerator($this->objectCacheKeyGenerator);
    }

    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(mixed $arguments, string $expectedCacheKey): void
    {
        $this->objectCacheKeyGenerator->expects($this->any())
            ->method('generate')
            ->with($this->isInstanceOf(\stdClass::class), 'sample_scope')
            ->willReturn('sample_key');

        $this->assertEquals($expectedCacheKey, $this->generator->generate($arguments));
    }

    public function generateDataProvider(): array
    {
        return [
            'number' => [
                'arguments' => 10,
                'expectedCacheKey' => sha1('10'),
            ],
            'string' => [
                'arguments' => 'sample_argument',
                'expectedCacheKey' => sha1('sample_argument'),
            ],
            'boolean' => [
                'arguments' => false,
                'expectedCacheKey' => sha1(false),
            ],
            'object' => [
                'arguments' => ['sample_scope' => new \stdClass()],
                'expectedCacheKey' => sha1('sample_key'),
            ],
            'object with scope in array' => [
                'arguments' => [
                    ['sample_scope' => [new \stdClass()]],
                ],
                'expectedCacheKey' => sha1('sample_key'),
            ],
            'mix of different types' => [
                'arguments' => [
                    'sample_scope' => new \stdClass(),
                    ['sample_scope' => new \stdClass()],
                    true,
                    'sample_string',
                    3,
                ],
                'expectedCacheKey' => sha1('sample_key|sample_key|1|sample_string|3'),
            ],
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalizeCacheKey(string $expectedCacheKey, string $actualCacheKey): void
    {
        $this->assertEquals($expectedCacheKey, UniversalCacheKeyGenerator::normalizeCacheKey($actualCacheKey));
    }

    public function normalizeDataProvider(): array
    {
        return [
            ['key111', 'key111'],
            ['%7Bkey111%7D', '{key111}'],
            ['%28key111%29', '(key111)'],
            ['%2Fkey%5C111', '/key\111'],
            ['%40key111', '@key111']
        ];
    }
}
