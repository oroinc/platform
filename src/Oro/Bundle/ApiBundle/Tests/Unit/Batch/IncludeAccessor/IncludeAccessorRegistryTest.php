<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\IncludeAccessor;

use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Container\ContainerInterface;

class IncludeAccessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyAccessors()
    {
        $registry = new IncludeAccessorRegistry(
            [],
            $this->createMock(ContainerInterface::class),
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getAccessor(new RequestType(['rest'])));
    }

    public function testAccessorFound()
    {
        $accessor1 = $this->createMock(IncludeAccessorInterface::class);
        $accessor2 = $this->createMock(IncludeAccessorInterface::class);

        $container = TestContainerBuilder::create()
            ->add('accessor1', $accessor1)
            ->add('accessor2', $accessor2)
            ->getContainer($this);

        $registry = new IncludeAccessorRegistry(
            [
                ['accessor1', 'first'],
                ['accessor2', 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertSame($accessor2, $registry->getAccessor(new RequestType(['second'])));
    }

    public function testAccessorNotFound()
    {
        $accessor1 = $this->createMock(IncludeAccessorInterface::class);
        $accessor2 = $this->createMock(IncludeAccessorInterface::class);

        $container = TestContainerBuilder::create()
            ->add('accessor1', $accessor1)
            ->add('accessor2', $accessor2)
            ->getContainer($this);

        $registry = new IncludeAccessorRegistry(
            [
                [$accessor1, 'first'],
                [$accessor2, 'second']
            ],
            $container,
            new RequestExpressionMatcher()
        );

        self::assertNull($registry->getAccessor(new RequestType(['another'])));
    }
}
