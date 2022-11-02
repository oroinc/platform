<?php

namespace Oro\Component\DependencyInjection\Tests\Unit;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass2;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass3;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendedContainerBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const EXTENSION = 'ext';

    private ExtendedContainerBuilder $builder;

    protected function setUp(): void
    {
        $extension = $this->createMock(ExtensionInterface::class);
        $extension->expects(self::any())
            ->method('getAlias')
            ->willReturn(self::EXTENSION);

        $this->builder = new ExtendedContainerBuilder();
        $this->builder->registerExtension($extension);
    }

    public function testSetExtensionConfigShouldOverwriteCurrentConfig(): void
    {
        $originalConfig = ['prop' => 'val'];
        $overwrittenConfig = [['p' => 'v']];

        $this->builder->prependExtensionConfig(self::EXTENSION, $originalConfig);
        self::assertEquals([$originalConfig], $this->builder->getExtensionConfig(self::EXTENSION));

        $this->builder->setExtensionConfig(self::EXTENSION, $overwrittenConfig);
        self::assertEquals($overwrittenConfig, $this->builder->getExtensionConfig(self::EXTENSION));
    }

    public function testMoveCompilerPassBefore(): void
    {
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeForNonDefaultPassType(): void
    {
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();

        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->moveCompilerPassBefore(
            get_class($srcPass),
            get_class($targetPass),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        self::assertSame(
            [$srcPass, $targetPass],
            $this->builder->getCompilerPassConfig()->getBeforeRemovingPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsBeforeTargetPass(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $anotherPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsAfterSrcPass(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $anotherPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenSrcPassIsAlreadyBeforeTargetPass(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenDoubleTargetPasses(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $target1Pass = new CompilerPass2();
        $target2Pass = new CompilerPass2();
        $this->builder->addCompilerPass($target1Pass);
        $this->builder->addCompilerPass($target2Pass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($target1Pass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $target1Pass,
                $target2Pass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeForEmptyPasses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass1::class));
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenSrcPassDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass1::class));
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenTargetPassDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass2::class));
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenTargetPassHasLowerPriority(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenTargetPassHasHigherPriority(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testAddCompilerPassAfterTargetPass(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));

        $beforeTargetPass = new CompilerPass3();
        $this->builder->addCompilerPass($beforeTargetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 17);

        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $beforeTargetPass,
                $srcPass,
                $targetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testAddCompilerPassBeforeTargetPass(): void
    {
        [
            $resolveClassPass,
            $registerAutoconfigureAttributesPass,
            $attributeAutoconfigurationPass,
            $resolveInstanceOfConditionalsPass,
            $registerEnvVarsProcessorsPass,
            $extensionCompilerPass
        ] = $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));

        $afterTargetPass = new CompilerPass3();
        $this->builder->addCompilerPass($afterTargetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 7);

        self::assertSame(
            [
                $resolveClassPass,
                $registerAutoconfigureAttributesPass,
                $attributeAutoconfigurationPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $afterTargetPass,
                $extensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }
}
