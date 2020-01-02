<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This class can be used to simplify testing of services use a service container.
 */
class TestContainerBuilder
{
    /** @var array */
    private $serviceMap = [];

    /** @var array */
    private $parameterMap = [];

    /**
     * @return TestContainerBuilder
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param string      $serviceId
     * @param object|null $service
     * @param int         $invalidBehavior
     *
     * @return self
     */
    public function add($serviceId, $service, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $this->serviceMap[$serviceId] = [$service, $invalidBehavior];

        return $this;
    }

    /**
     * @param string $parameterName
     * @param mixed  $parameterValue
     *
     * @return self
     */
    public function addParameter($parameterName, $parameterValue)
    {
        $this->parameterMap[$parameterName] = $parameterValue;

        return $this;
    }

    /**
     * @param \PHPUnit\Framework\TestCase $testCase
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    public function getContainer(\PHPUnit\Framework\TestCase $testCase)
    {
        $container = $testCase->getMockBuilder(ContainerInterface::class)->getMock();

        $container->expects(\PHPUnit\Framework\TestCase::any())
            ->method('has')
            ->willReturnCallback(function ($id) {
                return array_key_exists($id, $this->serviceMap);
            });
        $container->expects(\PHPUnit\Framework\TestCase::any())
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

        $container->expects(\PHPUnit\Framework\TestCase::any())
            ->method('hasParameter')
            ->willReturnCallback(function ($name) {
                return array_key_exists($name, $this->parameterMap);
            });
        $container->expects(\PHPUnit\Framework\TestCase::any())
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
