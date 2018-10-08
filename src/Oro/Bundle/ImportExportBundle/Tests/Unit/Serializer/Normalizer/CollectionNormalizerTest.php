<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\ImportExport\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;

class CollectionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

    /**
     * @var CollectionNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->serializer = $this->createMock('Oro\Bundle\ImportExportBundle\Serializer\Serializer');
        $this->normalizer = new CollectionNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @expectedExceptionMessage Serializer must implement
     */
    public function testSetInvalidSerializer()
    {
        $this->normalizer->setSerializer($this->createMock('Symfony\Component\Serializer\SerializerInterface'));
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));

        $collection = $this->createMock('Doctrine\Common\Collections\Collection');
        $this->assertTrue($this->normalizer->supportsNormalization($collection));
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->normalizer->supportsDenormalization(array(), $type));
    }

    public function supportsDenormalizationDataProvider()
    {
        return array(
            array('stdClass', false),
            array('ArrayCollection', true),
            array('Doctrine\Common\Collections\ArrayCollection', true),
            array('Doctrine\Common\Collections\ArrayCollection<Foo>', true),
            array('Doctrine\Common\Collections\ArrayCollection<Foo\Bar\Baz>', true),
            array('ArrayCollection<ArrayCollection<Foo\Bar\Baz>>', true),
        );
    }

    public function testNormalize()
    {
        $format = null;
        $context = array('context');

        $firstElement = $this->createMock(\stdClass::class);
        $secondElement = $this->createMock(\ArrayObject::class);
        $data = new ArrayCollection(array($firstElement, $secondElement));

        $this->serializer->expects($this->exactly(2))
            ->method('normalize')
            ->will(
                $this->returnValueMap(
                    array(
                        array($firstElement, $format, $context, 'first'),
                        array($secondElement, $format, $context, 'second'),
                    )
                )
            );

        $this->assertEquals(
            array('first', 'second'),
            $this->normalizer->normalize($data, $format, $context)
        );
    }

    public function testDenormalizeNotArray()
    {
        $this->serializer->expects($this->never())->method($this->anything());
        $this->assertEquals(
            new ArrayCollection(),
            $this->normalizer->denormalize('string', null)
        );
    }

    public function testDenormalizeSimple()
    {
        $this->serializer->expects($this->never())->method($this->anything());
        $data = array('foo', 'bar');
        $this->assertEquals(
            new ArrayCollection($data),
            $this->normalizer->denormalize($data, 'ArrayCollection', null)
        );
    }

    public function testDenormalizeWithItemType()
    {
        $format = null;
        $context = array();

        $fooEntity = new \stdClass();
        $barEntity = new \stdClass();

        $this->serializer->expects($this->exactly(2))
            ->method('denormalize')
            ->will(
                $this->returnValueMap(
                    array(
                        array('foo', 'ItemType', $format, $context, $fooEntity),
                        array('bar', 'ItemType', $format, $context, $barEntity),
                    )
                )
            );

        $this->assertEquals(
            new ArrayCollection(array($fooEntity, $barEntity)),
            $this->normalizer->denormalize(
                array('foo', 'bar'),
                'ArrayCollection<ItemType>',
                $format,
                $context
            )
        );
    }
}
