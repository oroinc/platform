<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class FilterNamesRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $defaultProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $firstProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $secondProvider;

    protected function setUp(): void
    {
        $this->defaultProvider = $this->createMock(FilterNames::class);
        $this->firstProvider = $this->createMock(FilterNames::class);
        $this->secondProvider = $this->createMock(FilterNames::class);
    }

    private function getRegistry(array $providers): FilterNamesRegistry
    {
        return new FilterNamesRegistry(
            $providers,
            TestContainerBuilder::create()
                ->add('default_provider', $this->defaultProvider)
                ->add('first_provider', $this->firstProvider)
                ->add('second_provider', $this->secondProvider)
                ->getContainer($this),
            new RequestExpressionMatcher()
        );
    }

    public function testGetFilterNamesForUnsupportedRequestType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a filter names provider for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getFilterNames($requestType);
    }

    public function testGetFilterNamesShouldReturnDefaultProviderForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnFirstProviderForFirstRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnSecondProviderForSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'second', 'another']);
        self::assertSame($this->secondProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnDefaultBagIfSpecificBagNotFound()
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first'],
                ['default_provider', '']
            ]
        );

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultProvider, $registry->getFilterNames($requestType));
    }
}
