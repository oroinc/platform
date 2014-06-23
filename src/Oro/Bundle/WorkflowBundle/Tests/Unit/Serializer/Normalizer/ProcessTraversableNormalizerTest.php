<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessTraversableNormalizer;

class ProcessTraversableNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var ProcessTraversableNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->serializer = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new ProcessTraversableNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @param mixed $data
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($data)
    {
        $format = 'json';
        $context = array();

        $expected = array();
        $iteration = 0;

        foreach ($data as $key => $value) {
            $serializedValue = json_encode($value);
            $this->serializer->expects($this->at($iteration))->method('normalize')->with($value, $format, $context)
                ->will($this->returnValue($serializedValue));

            $expected[$key] = $serializedValue;
            $iteration++;
        }

        $this->assertSame($expected, $this->normalizer->normalize($data, $format, $context));
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return array(
            'array' => array(
                'data' => array('first' => 1, 'second' => 2),
            ),
            'traversable' => array(
                'data' => new ArrayCollection(array('first' => 1, 'second' => 2)),
            ),
        );
    }

    public function testDenormalize()
    {
        $data = array('first' => json_encode(1), 'second' => json_encode(2));
        $format = 'json';
        $context = array();

        $expected = array();
        $iteration = 0;

        foreach ($data as $key => $value) {
            $denormalizedValue = json_decode($value);
            $this->serializer->expects($this->at($iteration))->method('denormalize')
                ->with($value, null, $format, $context)->will($this->returnValue($denormalizedValue));

            $expected[$key] = $denormalizedValue;
            $iteration++;
        }

        $this->assertSame($expected, $this->normalizer->denormalize($data, null, $format, $context));
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
            'null'        => array(null, false),
            'scalar'      => array('scalar', false),
            'array'       => array(array(), true),
            'traversable' => array(new ArrayCollection(), true),
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
            'array'  => array(array(), true),
        );
    }
}
