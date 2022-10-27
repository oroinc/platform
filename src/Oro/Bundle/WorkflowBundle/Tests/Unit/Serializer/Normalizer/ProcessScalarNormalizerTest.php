<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessScalarNormalizer;

class ProcessScalarNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessScalarNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProcessScalarNormalizer();
    }

    public function testNormalize()
    {
        $value = 'scalar';
        $this->assertEquals($value, $this->normalizer->normalize($value));
    }

    public function testDenormalize()
    {
        $value = 'scalar';
        $this->assertEquals($value, $this->normalizer->denormalize($value, ''));
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupportsDenormalization(mixed $data, bool $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($data, ''));
    }

    public function supportsDataProvider(): array
    {
        return [
            'null'   => [null, true],
            'scalar' => ['scalar', true],
            'array' => [[], false],
            'object' => [new \DateTime(), false],
        ];
    }
}
