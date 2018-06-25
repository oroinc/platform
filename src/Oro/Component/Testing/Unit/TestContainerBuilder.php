<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
        return new TestContainerBuilder();
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
        $this->serviceMap[] = [$serviceId, $invalidBehavior, $service];

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
        $this->parameterMap[] = [$parameterName, $parameterValue];

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
        if (!empty($this->serviceMap)) {
            $container->expects(\PHPUnit\Framework\TestCase::any())
                ->method('get')
                ->willReturnMap($this->serviceMap);
        }
        if (!empty($this->parameterMap)) {
            $container->expects(\PHPUnit\Framework\TestCase::any())
                ->method('getParameter')
                ->willReturnMap($this->parameterMap);
        }

        return $container;
    }
}
