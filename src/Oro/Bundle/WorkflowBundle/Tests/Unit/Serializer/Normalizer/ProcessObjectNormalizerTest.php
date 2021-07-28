<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessObjectNormalizer;

class ProcessObjectNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProcessObjectNormalizer
     */
    protected $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProcessObjectNormalizer();
    }

    public function testNormalize()
    {
        $object = new \DateTime();
        $serializedObject = base64_encode(serialize($object));

        $this->assertEquals(
            [ProcessObjectNormalizer::SERIALIZED => $serializedObject],
            $this->normalizer->normalize($object)
        );
    }

    /**
     * @param mixed $data
     * @param bool $expected
     *
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->denormalize($data, ''));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
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
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider()
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
    public function testSupportsDenormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($data, ''));
    }

    public function supportsDenormalizationDataProvider()
    {
        return [
            'null' => [null, false],
            'scalar' => ['scalar', false],
            'array' => [['key' => 'value'], false],
            'object' => [[ProcessObjectNormalizer::SERIALIZED => 'serialised_data'], true],
        ];
    }
}
