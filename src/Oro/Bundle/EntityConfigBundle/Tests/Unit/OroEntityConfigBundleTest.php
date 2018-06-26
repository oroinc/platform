<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\AttributeTypePass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\EntityConfigPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceMethodPass;
use Oro\Bundle\EntityConfigBundle\OroEntityConfigBundle;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEntityConfigBundleTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroEntityConfigBundle */
    protected $bundle;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    protected function setUp()
    {
        $this->bundle = new OroEntityConfigBundle();

        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
    }

    public function testBuild()
    {
        $compilerPasses = new ArrayCollection();

        $this->containerBuilder->expects($this->any())
            ->method('addCompilerPass')
            ->willReturnCallback(
                function (CompilerPassInterface $compilerPass) use ($compilerPasses) {
                    $compilerPasses->add(get_class($compilerPass));
                }
            );

        $this->bundle->build($this->containerBuilder);

        $this->assertEquals(
            [
                ServiceMethodPass::class,
                EntityConfigPass::class,
                AttributeBlockTypeMapperPass::class,
                AttributeTypePass::class,
                DoctrineOrmMappingsPass::class,
                DoctrineOrmMappingsPass::class,
                DefaultFallbackExtensionPass::class
            ],
            $compilerPasses->toArray()
        );
    }
}
