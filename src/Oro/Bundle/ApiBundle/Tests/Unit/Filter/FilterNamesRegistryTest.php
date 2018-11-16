<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class FilterNamesRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $defaultProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $firstProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterNames */
    private $secondProvider;

    protected function setUp()
    {
        $this->defaultProvider = $this->createMock(FilterNames::class);
        $this->firstProvider = $this->createMock(FilterNames::class);
        $this->secondProvider = $this->createMock(FilterNames::class);
    }

    /**
     * @param array $providers
     *
     * @return FilterNamesRegistry
     */
    private function getRegistry(array $providers)
    {
        return new FilterNamesRegistry(
            $providers,
            new RequestExpressionMatcher()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find a filter names provider for the request "rest,another".
     */
    public function testGetFilterNamesForUnsupportedRequestType()
    {
        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getFilterNames($requestType);
    }

    public function testGetFilterNamesShouldReturnDefaultProviderForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnFirstProviderForFirstRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnSecondProviderForSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'second', 'another']);
        self::assertSame($this->secondProvider, $registry->getFilterNames($requestType));
    }

    public function testGetFilterNamesShouldReturnDefaultBagIfSpecificBagNotFound()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first'],
                [$this->defaultProvider, '']
            ]
        );

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultProvider, $registry->getFilterNames($requestType));
    }
}
