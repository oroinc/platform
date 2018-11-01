<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class TaggedServicesCompilerPassCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    public function assertTaggedServicesRegistered(
        CompilerPassInterface $compilerPass,
        $serviceId,
        $tagName,
        $addMethodName
    ) {
        $this->assetProcessSkipWithoutServiceDefinition($compilerPass, $serviceId);
        $this->assertProcessSkipWithoutTaggedServices($compilerPass, $serviceId, $tagName);
        $this->assertProcess($compilerPass, $serviceId, $tagName, $addMethodName);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     */
    private function assetProcessSkipWithoutServiceDefinition(CompilerPassInterface $compilerPass, $serviceId)
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->assertHasDefinitionCall($containerBuilder, $serviceId, false);

        $compilerPass->process($containerBuilder);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     */
    private function assertProcessSkipWithoutTaggedServices(CompilerPassInterface $compilerPass, $serviceId, $tagName)
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->assertHasDefinitionCall($containerBuilder, $serviceId, true);
        $this->assertFindTaggedServiceIds($containerBuilder, $tagName, []);

        $containerBuilder->expects($this->never())
            ->method('getDefinition')
            ->with(call_user_func_array([$this, 'logicalOr'], (array)$serviceId));

        $compilerPass->process($containerBuilder);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    private function assertProcess(CompilerPassInterface $compilerPass, $serviceId, $tagName, $addMethodName)
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->assertHasDefinitionCall($containerBuilder, $serviceId, true);

        $this->assertFindTaggedServiceIds(
            $containerBuilder,
            $tagName,
            [
                'taggedService1' => [
                    ['priority' => 20, 'alias' => 'taggedService1Alias'],
                ],
                'taggedService2' => [
                ],
                'taggedService3' => [
                    ['priority' => 10, 'alias' => 'taggedService3Alias'],
                ]
            ]
        );
        $this->assertServicesRegistrationByTag(
            $containerBuilder,
            $serviceId,
            [
                ['taggedService2', 'taggedService2'],
                ['taggedService3', 'taggedService3Alias'],
                ['taggedService1', 'taggedService1Alias']
            ],
            $addMethodName
        );

        $compilerPass->process($containerBuilder);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $containerBuilder
     * @param string|array $serviceId
     * @param bool $result
     */
    private function assertHasDefinitionCall(
        \PHPUnit\Framework\MockObject\MockObject $containerBuilder,
        $serviceId,
        $result
    ) {
        $serviceId = $this->paramToConsecutive($serviceId);

        $containerBuilder->expects($this->exactly(count($serviceId)))
            ->method('hasDefinition')
            ->withConsecutive(...$serviceId)
            ->willReturn($result);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $containerBuilder
     * @param string|array $tagName
     * @param mixed $result
     */
    private function assertFindTaggedServiceIds(
        \PHPUnit\Framework\MockObject\MockObject $containerBuilder,
        $tagName,
        $result
    ) {
        $tagName = $this->paramToConsecutive($tagName);

        $containerBuilder->expects($this->exactly(count($tagName)))
            ->method('findTaggedServiceIds')
            ->withConsecutive(...$tagName)
            ->willReturn($result);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $containerBuilder
     * @param string|array $serviceId
     * @param array $taggedServices
     * @param $addMethodName
     */
    private function assertServicesRegistrationByTag(
        \PHPUnit\Framework\MockObject\MockObject $containerBuilder,
        $serviceId,
        array $taggedServices,
        $addMethodName
    ) {
        $serviceId = $this->paramToConsecutive($serviceId);
        $addMethodName = (array)$addMethodName;

        $returnedResult = [];
        $itemsCount = count($serviceId);
        for ($i = 0; $i < $itemsCount; $i++) {
            $service = $this->getMockBuilder(Definition::class)
                ->disableOriginalConstructor()
                ->getMock();

            $methodParameters = [];
            foreach ($taggedServices as $taggedService) {
                $methodParameters[] = [$addMethodName[$i], [new Reference($taggedService[0]), $taggedService[1]]];
            }
            $service->expects($this->exactly(count($taggedServices)))
                ->method('addMethodCall')
                ->withConsecutive(...$methodParameters);
            $returnedResult[] = $service;
        }

        $containerBuilder->expects($this->exactly(count($serviceId)))
            ->method('getDefinition')
            ->withConsecutive(...$serviceId)
            ->willReturnOnConsecutiveCalls(...$returnedResult);
    }


    /**
     * @param string|array $param
     * @return array
     */
    private function paramToConsecutive($param): array
    {
        $param = (array)$param;
        foreach ($param as &$paramVal) {
            $paramVal = [$paramVal];
        }
        unset($paramVal);

        return $param;
    }
}
