<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterInterface;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Container\ContainerInterface;

class FileSplitterRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptySplitters()
    {
        $registry = new FileSplitterRegistry(
            [],
            $this->createMock(ContainerInterface::class),
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getSplitter(new RequestType(['rest'])));
    }

    public function testSplitterFound()
    {
        $splitter1 = $this->createMock(FileSplitterInterface::class);
        $splitter2 = $this->createMock(FileSplitterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('splitter1', $splitter1)
            ->add('splitter2', $splitter2)
            ->getContainer($this);

        $registry = new FileSplitterRegistry(
            [
                ['splitter1', 'first'],
                ['splitter2', 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertSame($splitter2, $registry->getSplitter(new RequestType(['second'])));
    }

    public function testSplitterNotFound()
    {
        $splitter1 = $this->createMock(FileSplitterInterface::class);
        $splitter2 = $this->createMock(FileSplitterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('splitter1', $splitter1)
            ->add('splitter2', $splitter2)
            ->getContainer($this);

        $registry = new FileSplitterRegistry(
            [
                [$splitter1, 'first'],
                [$splitter2, 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getSplitter(new RequestType(['another'])));
    }
}
