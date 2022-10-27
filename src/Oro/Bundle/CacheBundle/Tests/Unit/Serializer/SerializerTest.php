<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Serializer;

use Oro\Bundle\CacheBundle\Serializer\Serializer;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SerializerTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateWithoutParameters(): void
    {
        $serializer = new Serializer();
        self::assertInstanceOf(Serializer::class, $serializer);
    }

    public function testCreateWithArrayParameters(): void
    {
        $normalizers = [
            $this->createMock(NormalizerInterface::class)
        ];
        $encoders = [
            $this->createMock(EncoderInterface::class)
        ];

        $serializer = new Serializer($normalizers, $encoders);
        self::assertInstanceOf(Serializer::class, $serializer);
    }

    public function testCreateWithIteratorParameters(): void
    {
        $normalizers = new \ArrayIterator([
            $this->createMock(NormalizerInterface::class)
        ]);
        $encoders = new \ArrayIterator([
            $this->createMock(EncoderInterface::class)
        ]);

        $serializer = new Serializer($normalizers, $encoders);
        self::assertInstanceOf(Serializer::class, $serializer);
    }

    public function testCreateWithIteratorAggregateParameters(): void
    {
        $normalizers = new RewindableGenerator([$this, 'getNormalizersGenerator'], 1);
        $encoders = new RewindableGenerator([$this, 'getEncodersGenerator'], 1);

        $serializer = new Serializer($normalizers, $encoders);
        self::assertInstanceOf(Serializer::class, $serializer);
    }

    public function getNormalizersGenerator()
    {
        yield $this->createMock(NormalizerInterface::class);
    }

    public function getEncodersGenerator()
    {
        yield $this->createMock(EncoderInterface::class);
    }
}
