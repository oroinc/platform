<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessObjectNormalizer;

class ProcessObjectNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessObjectNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->normalizer = new ProcessObjectNormalizer();
    }

    public function testNormalize()
    {
        $object = new \DateTime();
        $serializedObject = base64_encode(serialize($object));

        $this->assertEquals(
            array(ProcessObjectNormalizer::SERIALIZED => $serializedObject),
            $this->normalizer->normalize($object)
        );
    }

    /**
     * @param mixed $data
     * @param bool $expected
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->denormalize($data, null));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        $object = new \DateTime();
        $serializedObject = base64_encode(serialize($object));

        return array(
            'invalid value' => array(
                'data' => array(ProcessObjectNormalizer::SERIALIZED => null),
                'expected' => null,
            ),
            'valid object' => array(
                'data' => array(ProcessObjectNormalizer::SERIALIZED => $serializedObject),
                'expected' => $object,
            ),
        );
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
        return array(
            'null'   => array(null, false),
            'scalar' => array('scalar', false),
            'object' => array(new \DateTime(), true),
        );
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($data, null));
    }

    public function supportsDenormalizationDataProvider()
    {
        return array(
            'null'   => array(null, false),
            'scalar' => array('scalar', false),
            'array'  => array(array('key' => 'value'), false),
            'object' => array(array(ProcessObjectNormalizer::SERIALIZED => 'serialised_data'), true),
        );
    }
}
