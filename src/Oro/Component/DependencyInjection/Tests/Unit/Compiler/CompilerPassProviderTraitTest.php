<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Tests\Unit\Stub\CompilerPassProviderStub;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPassProviderTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompilerPassProviderStub
     */
    private $stub;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->stub = new CompilerPassProviderStub();
    }

    /**
     * @param array $expectedBeforeOptimizationPasses
     * @param array|null $expectedResult
     *
     * @dataProvider getDataProvider
     */
    public function testFindCompilerPassByClassName(array $expectedBeforeOptimizationPasses, $expectedResult)
    {
        $compilerPassConfig = $this->createMock(PassConfig::class);
        $compilerPassConfig->expects($this->once())
            ->method('getBeforeOptimizationPasses')
            ->willReturn($expectedBeforeOptimizationPasses);

        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getCompilerPassConfig')
            ->willReturn($compilerPassConfig);

        $this->assertEquals($expectedResult, $this->stub->getStdClassCompilerPass($container));
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        $object = new \stdClass();

        return [
            'not found' => [
                'expectedBeforeOptimizationPasses' => [],
                'expectedResult' => null
            ],
            'found' => [
                'expectedBeforeOptimizationPasses' => [$object],
                'expectedResult' => $object
            ]
        ];
    }
}
