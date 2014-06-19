<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;

class SerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected function setUp()
    {
        $this->serializer = new Serializer();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('Symfony\Component\Serializer\Serializer', $this->serializer);
    }

    public function testGetNormalizer()
    {
        $supportedNormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');
        $supportedNormalizer
            ->expects($this->once())
            ->method('supportsNormalization')
            ->will($this->returnValue(true));

        $nonSupportedNormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');
        $nonSupportedNormalizer
            ->expects($this->once())
            ->method('supportsNormalization')
            ->will($this->returnValue(false));

        $denormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');
        $denormalizer
            ->expects($this->never())
            ->method('supportsDenormalization')
            ->will($this->returnValue(true));

        $this->serializer = new Serializer([$denormalizer, $nonSupportedNormalizer, $supportedNormalizer]);

        $this->serializer->supportsNormalization(new \stdClass());
    }

    public function testGetDenormalizer()
    {
        $normalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');
        $normalizer
            ->expects($this->never())
            ->method('supportsNormalization')
            ->will($this->returnValue(true));

        $supportedDenormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');
        $supportedDenormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnValue(true));

        $nonSupportedDenormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');
        $nonSupportedDenormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnValue(false));

        $this->serializer = new Serializer([$normalizer, $nonSupportedDenormalizer, $supportedDenormalizer]);

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
    }

    public function testGetNrmalizerFailed()
    {
        $this->serializer = new Serializer();

        $this->serializer->supportsNormalization(new \stdClass(), 'test');
    }

    public function testGetDenormalizerFailed()
    {
        $this->serializer = new Serializer();

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testNormalizeNoMatch()
    {
        $this->serializer = new Serializer(array($this->getMock('Symfony\Component\Serializer\Normalizer\CustomNormalizer')));
        $this->serializer->normalize(new \stdClass, 'xml');
    }

    public function testNormalizeTraversable()
    {
        $this->serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $result = $this->serializer->serialize(new TraversableDummy, 'json');
        $this->assertEquals('{"foo":"foo","bar":"bar"}', $result);
    }

    public function testNormalizeGivesPriorityToInterfaceOverTraversable()
    {
        $this->serializer = new Serializer(array(new CustomNormalizer), array('json' => new JsonEncoder()));
        $result = $this->serializer->serialize(new NormalizableTraversableDummy, 'json');
        $this->assertEquals('{"foo":"normalizedFoo","bar":"normalizedBar"}', $result);
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testNormalizeOnDenormalizer()
    {
        $this->serializer = new Serializer(array(new TestDenormalizer()), array());
        $this->assertTrue($this->serializer->normalize(new \stdClass, 'json'));
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDenormalizeNoMatch()
    {
        $this->serializer = new Serializer(array($this->getMock('Symfony\Component\Serializer\Normalizer\CustomNormalizer')));
        $this->serializer->denormalize('foo', 'stdClass');
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDenormalizeOnNormalizer()
    {
        $this->serializer = new Serializer(array(new TestNormalizer()), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $this->assertTrue($this->serializer->denormalize(json_encode($data), 'stdClass', 'json'));
    }
}
