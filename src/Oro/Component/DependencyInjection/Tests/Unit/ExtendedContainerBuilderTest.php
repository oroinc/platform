<?php

namespace Oro\Component\DependencyInjection\Tests\Unit;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass2;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass3;

class ExtendedContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    const EXTENSION = 'ext';

    /** @var ExtendedContainerBuilder */
    private $builder;

    public function setUp()
    {
        $extension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
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
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [$srcPass, $targetPass],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeForNonDefaultPassType()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($targetPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->addCompilerPass($srcPass, PassConfig::TYPE_BEFORE_REMOVING);
        $this->builder->moveCompilerPassBefore(
            get_class($srcPass),
            get_class($targetPass),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $this->assertSame(
            [$srcPass, $targetPass],
            $this->builder->getCompilerPassConfig()->getBeforeRemovingPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsBeforeTargetPass()
    {
        $srcPass     = new CompilerPass1();
        $targetPass  = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [$anotherPass, $srcPass, $targetPass],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenThereIsAnotherPassExistsAfterSrcPass()
    {
        $srcPass     = new CompilerPass1();
        $targetPass  = new CompilerPass2();
        $anotherPass = new CompilerPass3();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($anotherPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [$srcPass, $targetPass, $anotherPass],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenSrcPassIsAlreadyBeforeTargetPass()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
        $this->assertSame(
            [$srcPass, $targetPass],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    public function testMoveCompilerPassBeforeWhenDoubleTargetPasses()
    {
        $srcPass     = new CompilerPass1();
        $target1Pass = new CompilerPass2();
        $target2Pass = new CompilerPass2();
        $this->builder->addCompilerPass($target1Pass);
        $this->builder->addCompilerPass($target2Pass);
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($target1Pass));
        $this->assertSame(
            [$srcPass, $target1Pass, $target2Pass],
            $this->builder->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown compiler pass "Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1"
     */
    // @codingStandardsIgnoreEnd
    public function testMoveCompilerPassBeforeForEmptyPasses()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown compiler pass "Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1"
     */
    // @codingStandardsIgnoreEnd
    public function testMoveCompilerPassBeforeWhenSrcPassDoesNotExist()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($targetPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown compiler pass "Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass2"
     */
    // @codingStandardsIgnoreEnd
    public function testMoveCompilerPassBeforeWhenTargetPassDoesNotExist()
    {
        $srcPass    = new CompilerPass1();
        $targetPass = new CompilerPass2();
        $this->builder->addCompilerPass($srcPass);
        $this->builder->moveCompilerPassBefore(get_class($srcPass), get_class($targetPass));
    }
}
