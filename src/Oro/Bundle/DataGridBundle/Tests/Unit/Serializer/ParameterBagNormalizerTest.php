<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Serializer;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Serializer\ParameterBagNormalizer;
use PHPUnit\Framework\TestCase;

class ParameterBagNormalizerTest extends TestCase
{
    private ParameterBagNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer = new ParameterBagNormalizer();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupportsNormalization(object $object, bool $expected): void
    {
        $this->assertSame($expected, $this->normalizer->supportsNormalization($object));
    }

    public function testNormalize(): void
    {
        $data = ['_attribute1' => 'value1', '_attribute2' => 'value2'];
        $parameters = new ParameterBag($data);
        $this->assertEquals($data, $this->normalizer->normalize($parameters));
    }

    public function testDenormalize(): void
    {
        $data = ['_attribute1' => 'value1', '_attribute2' => 'value2'];
        $parameters = new ParameterBag($data);
        $this->assertEquals($parameters, $this->normalizer->denormalize($data, ParameterBag::class));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supports' => [new ParameterBag(), true],
            'unsupported' => [new \stdClass(), false]
        ];
    }
}
