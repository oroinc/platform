<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Generator;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheDataSerializer;
use Symfony\Component\Serializer\SerializerInterface;

class ObjectCacheDataSerializerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;

    /**
     * @var ObjectCacheDataSerializer
     */
    private $dataSerializer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->dataSerializer = new ObjectCacheDataSerializer($this->serializer);
    }

    public function testConvertToString()
    {
        $object = new \stdClass();
        $scope = 'someScope';
        $expectedResult = 'serialized_data';
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($object, 'json', ['groups' => [$scope]])
            ->willReturn($expectedResult);
        $result = $this->dataSerializer->convertToString($object, $scope);
        self::assertEquals($expectedResult, $result);
    }
}
