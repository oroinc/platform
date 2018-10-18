<?php

namespace Oro\Component\DependencyInjection\Tests\Unit;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass2;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass3;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ExtendedContainerBuilderTest extends \PHPUnit\Framework\TestCase
{
    const EXTENSION = 'ext';

    /** @var ExtendedContainerBuilder */
    private $builder;

    public function setUp()
    {
        /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(ExtensionInterface::class);
        $extension
            ->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue(static::EXTENSION));

        $this->builder = new ExtendedContainerBuilder();
        $this->builder->registerExtension($extension);
    }

    public function testSetExtensionConfigShouldOverwriteCurrentConfig()
    {
        $originalConfig    = ['prop' => 'val'];
        $overwrittenConfig = [['p' => 'v']];

        $this->builder->prependExtensionConfig(static::EXTENSION, $originalConfig);
        $this->assertEquals([$originalConfig], $this->builder->getExtensionConfig(static::EXTENSION));

        $this->builder->setExtensionConfig(static::EXTENSION, $overwrittenConfig);
        $this->assertEquals($overwrittenConfig, $this->builder->getExtensionConfig(static::EXTENSION));
    }

    public function testMoveCompilerPassBefore()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeForNonDefaultPassType()
    {
        $srcPass = new CompilerPass1();
        $targetPass = new CompilerPass2();

        [$resolvePrivatesPass] = $this->builder->getCompilerPassConfig()->getBeforeRemovingPasses();

        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->moveCompilerPassBefore(
            get_class($srcPass),
            get_class($targetPass),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $this->assertSame(
            [$srcPass, $targetPass, $resolvePrivatesPass],
            $this->builder->getCompilerPassConfig()->getBeforeRemovingPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsBeforeTargetPass()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $srcPass     = new CompilerPass1();
        $targetPass  = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $anotherPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsAfterSrcPass()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass     = new CompilerPass1();
        $targetPass  = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $anotherPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenSrcPassIsAlreadyBeforeTargetPass()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenDoubleTargetPasses()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass     = new CompilerPass1();
        $target1Pass = new CompilerPass2();
        $target2Pass = new CompilerPass2();
        $this->builder->addCompilerPass($target1Pass);
        $this->builder->addCompilerPass($target2Pass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($target1Pass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $target1Pass,
                $target2Pass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeForEmptyPasses()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass1::class));
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenSrcPassDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass1::class));
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenTargetPassDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown compiler pass "%s"', CompilerPass2::class));
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    public function testMoveCompilerPassBeforeWhenTargetPassHasLowerPriority()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenTargetPassHasHigherPriority()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testAddCompilerPassAfterTargetPass()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));

        $beforeTargetPass = new CompilerPass3();
        $this->builder->addCompilerPass($beforeTargetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 17);

        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $beforeTargetPass,
                $srcPass,
                $targetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testAddCompilerPassBeforeTargetPass()
    {
        [$resolveClassPass, $resolveInstanceOfConditionalsPass, $registerEnvVarsProcessorsPass, $xtensionCompilerPass] =
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));

        $afterTargetPass = new CompilerPass3();
        $this->builder->addCompilerPass($afterTargetPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 7);

        $this->assertSame(
            [
                $resolveClassPass,
                $resolveInstanceOfConditionalsPass,
                $registerEnvVarsProcessorsPass,
                $srcPass,
                $targetPass,
                $afterTargetPass,
                $xtensionCompilerPass,
            ],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }
}
