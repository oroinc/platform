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
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($proc, $procForCompare, $iterations)
    {
        $normalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');

        $supportedDenormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');
        $supportedDenormalizer
            ->expects($this->exactly($iterations))
            ->method('supportsDenormalization')
            ->will($this->returnValue(true));

        $nonSupportedDenormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');
        $nonSupportedDenormalizer
            ->expects($this->exactly($iterations))
            ->method('supportsDenormalization')
            ->will($this->returnValue(false));

        $this->serializer = new Serializer([$normalizer, $nonSupportedDenormalizer, $supportedDenormalizer]);

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
        $this->serializer->denormalize(new \stdClass(), 'test', null, [Serializer::PROCESSOR_ALIAS_KEY => $proc]);
        $this->serializer->denormalize(
            new \stdClass(),
            'test',
            null,
            [Serializer::PROCESSOR_ALIAS_KEY => $procForCompare]
        );
    }

    public function denormalizeDataProvider()
    {
        return [
            'with cache'    => ['proc', 'proc', 2],
            'without cache' => ['proc', 'proc1', 3],

        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($proc, $procForCompare, $iterations)
    {
        $supportedNormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');
        $supportedNormalizer
            ->expects($this->exactly($iterations))
            ->method('supportsNormalization')
            ->will($this->returnValue(true));

        $nonSupportedNormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface');
        $nonSupportedNormalizer
            ->expects($this->exactly($iterations))
            ->method('supportsNormalization')
            ->will($this->returnValue(false));

        $denormalizer = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface');

        $this->serializer = new Serializer([$denormalizer, $nonSupportedNormalizer, $supportedNormalizer]);

        $this->serializer->supportsNormalization(new \stdClass());
        $this->serializer->normalize(new \stdClass(), null, [Serializer::PROCESSOR_ALIAS_KEY => $proc]);
        $this->serializer->normalize(
            new \stdClass(),
            null,
            [Serializer::PROCESSOR_ALIAS_KEY => $procForCompare]
        );
    }

    public function normalizeDataProvider()
    {
        return [
            'with cache'    => ['proc', 'proc', 4],
            'without cache' => ['proc', 'proc1', 5],

        ];
    }
}
