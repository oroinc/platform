<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Sorter;

use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

class FiltersSorterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FiltersSorterInterface */
    private $defaultSorter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FiltersSorterInterface */
    private $firstSorter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FiltersSorterInterface */
    private $secondSorter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        $this->defaultSorter = $this->createMock(FiltersSorterInterface::class);
        $this->firstSorter = $this->createMock(FiltersSorterInterface::class);
        $this->secondSorter = $this->createMock(FiltersSorterInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $sorters): FiltersSorterRegistry
    {
        return new FiltersSorterRegistry(
            $sorters,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetSorterForUnsupportedRequestType()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second']
        ]);

        $requestType = new RequestType(['rest', 'another']);
        self::assertNull($registry->getSorter($requestType));
        // test internal cache
        self::assertNull($registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnDefaultSorterForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_sorter')
            ->willReturn($this->defaultSorter);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnFirstSorterForFirstRequestType()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_sorter')
            ->willReturn($this->firstSorter);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnSecondSorterForSecondRequestType()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_sorter')
            ->willReturn($this->secondSorter);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->secondSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnDefaultSorterIfSpecificSorterNotFound()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_sorter')
            ->willReturn($this->defaultSorter);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
    }

    public function testReset()
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('first_sorter')
            ->willReturn($this->firstSorter);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));

        $registry->reset();
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
    }
}
