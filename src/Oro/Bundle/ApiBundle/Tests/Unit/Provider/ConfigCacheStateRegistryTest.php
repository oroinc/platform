<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCacheStateRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;

class ConfigCacheStateRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheStateInterface */
    private $defaultState;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheStateInterface */
    private $firstState;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheStateInterface */
    private $secondState;

    protected function setUp(): void
    {
        $this->defaultState = $this->createMock(ConfigCacheStateInterface::class);
        $this->firstState = $this->createMock(ConfigCacheStateInterface::class);
        $this->secondState = $this->createMock(ConfigCacheStateInterface::class);
    }

    private function getRegistry(array $states): ConfigCacheStateRegistry
    {
        return new ConfigCacheStateRegistry(
            $states,
            new RequestExpressionMatcher()
        );
    }

    public function testGetConfigCacheStateForUnsupportedRequestType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a config cache state service for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getConfigCacheState($requestType);
    }

    public function testGetConfigCacheStateShouldReturnDefaultStateForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->defaultState, '!first&!second'],
                [$this->firstState, 'first'],
                [$this->secondState, 'second']
            ]
        );

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultState, $registry->getConfigCacheState($requestType));
        // test internal cache
        self::assertSame($this->defaultState, $registry->getConfigCacheState($requestType));
    }

    public function testGetConfigCacheStateShouldReturnFirstStateForFirstRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->defaultState, '!first&!second'],
                [$this->firstState, 'first'],
                [$this->secondState, 'second']
            ]
        );

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstState, $registry->getConfigCacheState($requestType));
        // test internal cache
        self::assertSame($this->firstState, $registry->getConfigCacheState($requestType));
    }

    public function testGetConfigCacheStateShouldReturnSecondStateForSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->defaultState, '!first&!second'],
                [$this->firstState, 'first'],
                [$this->secondState, 'second']
            ]
        );

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondState, $registry->getConfigCacheState($requestType));
        // test internal cache
        self::assertSame($this->secondState, $registry->getConfigCacheState($requestType));
    }

    public function testGetConfigCacheStateShouldReturnDefaultStateIfSpecificStateNotFound()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstState, 'first'],
                [$this->defaultState, '']
            ]
        );

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultState, $registry->getConfigCacheState($requestType));
        // test internal cache
        self::assertSame($this->defaultState, $registry->getConfigCacheState($requestType));
    }
}
