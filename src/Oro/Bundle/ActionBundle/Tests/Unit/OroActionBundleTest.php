<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;
use Oro\Bundle\ActionBundle\OroActionBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroActionBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $compilerPasses = [
            [
                'class' => CompilerPass\ConditionPass::class,
                'type' => PassConfig::TYPE_AFTER_REMOVING
            ],
            [
                'class' => CompilerPass\ActionPass::class,
                'type' => PassConfig::TYPE_AFTER_REMOVING
            ],
            [
                'class' => CompilerPass\MassActionProviderPass::class,
                'type' => PassConfig::TYPE_AFTER_REMOVING
            ],
            [
                'class' => CompilerPass\ButtonProviderPass::class,
                'type' => PassConfig::TYPE_AFTER_REMOVING
            ],
            [
                'class' => CompilerPass\DoctrineTypeMappingProviderPass::class,
                'type' => PassConfig::TYPE_BEFORE_OPTIMIZATION
            ],
            [
                'class' => CompilerPass\OperationRegistryFilterPass::class,
                'type' => PassConfig::TYPE_BEFORE_OPTIMIZATION
            ],
            [
                'class' => CompilerPass\DuplicatorFilterPass::class,
                'type' => PassConfig::TYPE_BEFORE_REMOVING
            ],
            [
                'class' => CompilerPass\DuplicatorMatcherPass::class,
                'type' => PassConfig::TYPE_BEFORE_REMOVING
            ],
            [
                'class' => CompilerPass\ActionLocatorPass::class,
                'type' => PassConfig::TYPE_BEFORE_OPTIMIZATION
            ],
            [
                'class' => CompilerPass\ConditionLocatorPass::class,
                'type' => PassConfig::TYPE_BEFORE_OPTIMIZATION
            ],
        ];

        foreach ($compilerPasses as $index => $data) {
            $containerBuilder->expects($this->at($index))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf($data['class']),
                    $data['type']
                );
        }

        $bundle = new OroActionBundle();
        $bundle->build($containerBuilder);
    }
}
