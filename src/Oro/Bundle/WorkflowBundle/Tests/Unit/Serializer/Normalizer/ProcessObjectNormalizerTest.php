<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessObjectNormalizer;
use PHPUnit\Framework\TestCase;

class ProcessObjectNormalizerTest extends TestCase
{
    private ProcessObjectNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer = new ProcessObjectNormalizer();
    }

    public function testNormalize(): void
    {
        $object = new \DateTime();
        $serializedObject = base64_encode(serialize($object));

        $this->assertEquals(
            [ProcessObjectNormalizer::SERIALIZED => $serializedObject],
            $this->normalizer->normalize($object)
        );
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(mixed $data, ?\DateTime $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->denormalize($data, ''));
    }

    public function denormalizeDataProvider(): array
    {
        $object = new \DateTime();
        $serializedObject = base64_encode(serialize($object));

        return [
            'invalid value' => [
                'data' => [ProcessObjectNormalizer::SERIALIZED => null],
                'expected' => null,
            ],
            'valid object' => [
                'data' => [ProcessObjectNormalizer::SERIALIZED => $serializedObject],
                'expected' => $object,
            ],
        ];
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            'null' => [null, false],
            'scalar' => ['scalar', false],
            'object' => [new \DateTime(), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, bool $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($data, ''));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'null' => [null, false],
            'scalar' => ['scalar', false],
            'array' => [['key' => 'value'], false],
            'object' => [[ProcessObjectNormalizer::SERIALIZED => 'serialised_data'], true],
        ];
    }
}
