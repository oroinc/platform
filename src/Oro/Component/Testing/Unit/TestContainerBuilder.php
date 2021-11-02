<?php

namespace Oro\Component\Testing\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This class can be used to simplify testing of services use a service container.
 */
class TestContainerBuilder
{
    private array $serviceMap = [];
    private array $parameterMap = [];

    public static function create(): self
    {
        return new self();
    }

    public function add(
        string $serviceId,
        ?object $service,
        int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
    ): self {
        $this->serviceMap[$serviceId] = [$service, $invalidBehavior];

        return $this;
    }

    public function addParameter(string $parameterName, mixed $parameterValue): self
    {
        $this->parameterMap[$parameterName] = $parameterValue;

        return $this;
    }

    public function getContainer(TestCase $testCase): ContainerInterface|MockObject
    {
        $container = $testCase->getMockBuilder(ContainerInterface::class)->getMock();

        $container->expects(TestCase::any())
            ->method('has')
            ->willReturnCallback(function ($id) {
                return array_key_exists($id, $this->serviceMap);
            });
        $container->expects(TestCase::any())
            ->method('get')
            ->willReturnCallback(function ($id) {
                if (!array_key_exists($id, $this->serviceMap)
                    || (
                        null === $this->serviceMap[$id][1]
                        && ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE === $this->serviceMap[$id][1]
                    )
                ) {
                    throw new ServiceNotFoundException(sprintf('The "%s" service does not exist.', $id));
                }

                return $this->serviceMap[$id][0];
            });

        $container->expects(TestCase::any())
            ->method('hasParameter')
            ->willReturnCallback(function ($name) {
                return array_key_exists($name, $this->parameterMap);
            });
        $container->expects(TestCase::any())
            ->method('getParameter')
            ->willReturnCallback(function ($name) {
                if (!array_key_exists($name, $this->parameterMap)) {
                    throw new InvalidArgumentException(sprintf('The "%s" parameter does not exist.', $name));
                }

                return $this->parameterMap[$name];
            });

        return $container;
    }
}
