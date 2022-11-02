<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Encoder;

use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderInterface;
use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Container\ContainerInterface;

class DataEncoderRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyEncoders()
    {
        $registry = new DataEncoderRegistry(
            [],
            $this->createMock(ContainerInterface::class),
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getEncoder(new RequestType(['rest'])));
    }

    public function testEncoderFound()
    {
        $encoder1 = $this->createMock(DataEncoderInterface::class);
        $encoder2 = $this->createMock(DataEncoderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('encoder1', $encoder1)
            ->add('encoder2', $encoder2)
            ->getContainer($this);

        $registry = new DataEncoderRegistry(
            [
                ['encoder1', 'first'],
                ['encoder2', 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertSame($encoder2, $registry->getEncoder(new RequestType(['second'])));
    }

    public function testEncoderNotFound()
    {
        $encoder1 = $this->createMock(DataEncoderInterface::class);
        $encoder2 = $this->createMock(DataEncoderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('encoder1', $encoder1)
            ->add('encoder2', $encoder2)
            ->getContainer($this);

        $registry = new DataEncoderRegistry(
            [
                [$encoder1, 'first'],
                [$encoder2, 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getEncoder(new RequestType(['another'])));
    }
}
