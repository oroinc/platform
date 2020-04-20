<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifierInterface;
use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifierRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Container\ContainerInterface;

class ChunkFileClassifierRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyClassifiers()
    {
        $registry = new ChunkFileClassifierRegistry(
            [],
            $this->createMock(ContainerInterface::class),
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getClassifier(new RequestType(['rest'])));
    }

    public function testClassifierFound()
    {
        $classifier1 = $this->createMock(ChunkFileClassifierInterface::class);
        $classifier2 = $this->createMock(ChunkFileClassifierInterface::class);

        $container = TestContainerBuilder::create()
            ->add('classifier1', $classifier1)
            ->add('classifier2', $classifier2)
            ->getContainer($this);

        $registry = new ChunkFileClassifierRegistry(
            [
                ['classifier1', 'first'],
                ['classifier2', 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertSame($classifier2, $registry->getClassifier(new RequestType(['second'])));
    }

    public function testClassifierNotFound()
    {
        $classifier1 = $this->createMock(ChunkFileClassifierInterface::class);
        $classifier2 = $this->createMock(ChunkFileClassifierInterface::class);

        $container = TestContainerBuilder::create()
            ->add('classifier1', $classifier1)
            ->add('classifier2', $classifier2)
            ->getContainer($this);

        $registry = new ChunkFileClassifierRegistry(
            [
                [$classifier1, 'first'],
                [$classifier2, 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getClassifier(new RequestType(['another'])));
    }
}
