<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class FallbackValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FallbackValueTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new FallbackValueTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(FallbackType|string|null $input, array $expected): void
    {
        $this->assertEquals($expected, $this->transformer->transform($input));
    }

    public function transformDataProvider(): array
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => ['value' => null, 'use_fallback' => false, 'fallback' => null],
            ],
            'scalar' => [
                'input'    => 'string',
                'expected' => ['value' => 'string', 'use_fallback' => false, 'fallback' => null],
            ],
            'fallback' => [
                'input'    => new FallbackType(FallbackType::SYSTEM),
                'expected' => ['value' => null, 'use_fallback' => true, 'fallback' => FallbackType::SYSTEM],
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(?array $input, ?string $expected): void
    {
        $this->assertSame($expected, $this->transformer->reverseTransform($input));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => null,
            ],
            'empty array' => [
                'input'    => [],
                'expected' => null,
            ],
            'empty values' => [
                'input'    => ['value' => null, 'fallback' => null],
                'expected' => '',
            ],
            'scalar' => [
                'input'    => ['value' => 'string', 'fallback' => null],
                'expected' => 'string',
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformWhenFallbackDataProvider
     */
    public function testReverseTransformWhenFallback(array $input, FallbackType|string $expected): void
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($input));
    }

    public function reverseTransformWhenFallbackDataProvider(): array
    {
        return [
            'fallback' => [
                'input' => ['value' => null, 'fallback' => FallbackType::SYSTEM, 'use_fallback' => true],
                'expected' => new FallbackType(FallbackType::SYSTEM),
            ],
            'when not use_fallback than value' => [
                'input' => ['value' => 'string', 'fallback' => FallbackType::SYSTEM, 'use_fallback' => false],
                'expected' => 'string',
            ],
            'when use_fallback than fallback' => [
                'input' => ['value' => 'string', 'fallback' => FallbackType::SYSTEM, 'use_fallback' => true],
                'expected' => new FallbackType(FallbackType::SYSTEM),
            ],
            'use_fallback required fallback' => [
                'input' => ['value' => 'string', 'use_fallback' => true],
                'expected' => 'string',
            ],
        ];
    }
}
