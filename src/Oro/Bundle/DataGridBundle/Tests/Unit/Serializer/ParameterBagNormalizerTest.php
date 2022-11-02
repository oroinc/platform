<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Serializer;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Serializer\ParameterBagNormalizer;

class ParameterBagNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBagNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ParameterBagNormalizer();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupportsNormalization(object $object, bool $expected)
    {
        $this->assertSame($expected, $this->normalizer->supportsNormalization($object));
    }

    public function testNormalize()
    {
        $data = ['_attribute1' => 'value1', '_attribute2' => 'value2'];
        $parameters = new ParameterBag($data);
        $this->assertEquals($data, $this->normalizer->normalize($parameters));
    }

    public function testDenormalize()
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
