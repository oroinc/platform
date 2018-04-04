<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ConfigLoaderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityManagerPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityMetadataBuilderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ExtensionPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\MigrationConfigPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\WarmerPass;
use Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub\ClassLoader;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @group dist
 */
class OroEntityExtendBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroEntityExtendBundle|\PHPUnit_Framework_MockObject_MockObject */
    private $bundle;

    /** @var KernelInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $kernel;

    /** @var array|ClassLoader[] */
    private $classLoaders;

    protected function setUp()
    {
        $this->createAutoloaders();

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->bundle = new OroEntityExtendBundle($this->kernel);
    }

    private function createAutoloaders()
    {
        $prefixes = [
            'Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils',
            'Oro\Bundle\InstallerBundle\CommandExecutor',
            'Symfony\Component\Process\ProcessBuilder'
        ];

        foreach ($prefixes as $prefix) {
            $classLoader = new ClassLoader($prefix, __DIR__ . DIRECTORY_SEPARATOR . 'Stub');
            $classLoader->register();

            $this->classLoaders[] = $classLoader;
        }
    }

    protected function tearDown()
    {
        $this->clearStaticCalls();

        foreach ($this->classLoaders as $loader) {
            $loader->unregister();
        }
    }

    public function testBootWhileUpdateConfig()
    {
        CommandExecutor::addExpectedCall('isCommandRunning', ['oro:entity-extend:update-config'], true);

        $this->bundle->boot();

        $this->assertStaticCalls(
            [
                'isCommandRunning' => [
                    ['oro:entity-extend:update-config']
                ],
                'isCurrentCommand' => [
                    ['oro:entity-extend:cache:', true],
                    ['oro:install', true],
                    ['oro:platform:upgrade20', true]
                ],
            ],
            [
                'registerClassLoader' => [
                    [null]
                ],
            ],
            []
        );
    }

    public function testBoot()
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->exactly(2))
            ->method('run')
            ->willReturn(0);

        $processBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->setMethods(['setTimeout', 'add', 'getProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $processBuilder
            ->method('setTimeout')
            ->willReturnSelf();
        $processBuilder
            ->method('add')
            ->willReturnSelf();
        $processBuilder->expects($this->exactly(2))
            ->method('getProcess')
            ->willReturn($process);

        ProcessBuilder::addExpectedCall('create', [], $processBuilder);
        ProcessBuilder::addExpectedCall('create', [], $processBuilder);

        $this->bundle->boot();

        $this->assertStaticCalls(
            [
                'isCommandRunning' => [
                    ['oro:entity-extend:update-config'],
                    ['oro:entity-extend:cache:check'],
                    ['oro:entity-extend:cache:warmup'],
                ],
                'isCurrentCommand' => [
                    ['oro:entity-extend:cache:', true],
                    ['oro:install', true],
                    ['oro:platform:upgrade20', true],
                    ['oro:entity-extend:update-config'],
                ],
                'getPhpExecutable' => [
                    [],
                    [],
                ]
            ],
            [
                'registerClassLoader' => [
                    [null]
                ],
                'getEntityCacheDir' => [
                    [null]
                ],
                'ensureDirExists' => [
                    [null]
                ],
                'getAliasesPath' => [
                    [null]
                ],
                'setAliases' => [
                    [null]
                ],
            ],
            [
                'create' => [
                    [],
                    []
                ],
            ]
        );
    }

    public function testBootNoNeedToWarmupAgain()
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run')
            ->willReturn(0);

        $processBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->setMethods(['setTimeout', 'add', 'getProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $processBuilder
            ->method('setTimeout')
            ->willReturnSelf();
        $processBuilder
            ->method('add')
            ->willReturnSelf();
        $processBuilder->expects($this->once())
            ->method('getProcess')
            ->willReturn($process);

        ProcessBuilder::addExpectedCall('create', [], $processBuilder);
        ProcessBuilder::addExpectedCall('create', [], $processBuilder);
        CommandExecutor::addExpectedCall('isCommandRunning', ['oro:entity-extend:cache:warmup'], true);
        CommandExecutor::addExpectedCall('isCommandRunning', ['oro:entity-extend:cache:warmup'], false);

        $this->bundle->boot();

        $this->assertStaticCalls(
            [
                'isCommandRunning' => [
                    ['oro:entity-extend:update-config'],
                    ['oro:entity-extend:cache:check'],
                    ['oro:entity-extend:cache:warmup'],
                    ['oro:entity-extend:cache:warmup'],
                ],
                'isCurrentCommand' => [
                    ['oro:entity-extend:cache:', true],
                    ['oro:install', true],
                    ['oro:platform:upgrade20', true],
                    ['oro:entity-extend:update-config'],
                ],
                'getPhpExecutable' => [
                    [],
                    [],
                ]
            ],
            [
                'registerClassLoader' => [
                    [null]
                ],
                'getEntityCacheDir' => [
                    [null]
                ],
                'ensureDirExists' => [
                    [null]
                ],
                'getAliasesPath' => [
                    [null]
                ],
                'setAliases' => [
                    [null]
                ],
            ],
            [
                'create' => [
                    [],
                    []
                ],
            ]
        );
    }

    public function testBootNoNeedToCheckAgain()
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run')
            ->willReturn(0);

        $processBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->setMethods(['setTimeout', 'add', 'getProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $processBuilder
            ->method('setTimeout')
            ->willReturnSelf();
        $processBuilder
            ->method('add')
            ->willReturnSelf();
        $processBuilder->expects($this->once())
            ->method('getProcess')
            ->willReturn($process);

        ProcessBuilder::addExpectedCall('create', [], $processBuilder);
        ProcessBuilder::addExpectedCall('create', [], $processBuilder);

        CommandExecutor::addExpectedCall('isCommandRunning', ['oro:entity-extend:cache:check'], true);

        $this->bundle->boot();

        $this->assertStaticCalls(
            [
                'isCommandRunning' => [
                    ['oro:entity-extend:update-config'],
                    ['oro:entity-extend:cache:check'],
                    ['oro:entity-extend:cache:check'],
                    ['oro:entity-extend:cache:warmup'],
                ],
                'isCurrentCommand' => [
                    ['oro:entity-extend:cache:', true],
                    ['oro:install', true],
                    ['oro:platform:upgrade20', true],
                    ['oro:entity-extend:update-config'],
                ],
                'getPhpExecutable' => [
                    [],
                    [],
                ]
            ],
            [
                'registerClassLoader' => [
                    [null]
                ],
                'getEntityCacheDir' => [
                    [null]
                ],
                'ensureDirExists' => [
                    [null]
                ],
                'getAliasesPath' => [
                    [null]
                ],
                'setAliases' => [
                    [null]
                ],
            ],
            [
                'create' => [
                    [],
                    []
                ],
            ]
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBootCheckConfigsException()
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run')
            ->willReturn(1);

        $processBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->setMethods(['setTimeout', 'add', 'getProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $processBuilder
            ->method('setTimeout')
            ->willReturnSelf();
        $processBuilder
            ->method('add')
            ->willReturnSelf();
        $processBuilder->expects($this->once())
            ->method('getProcess')
            ->willReturn($process);

        ProcessBuilder::addExpectedCall('create', [], $processBuilder);

        $this->bundle->boot();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBootCacheWarmupException()
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->exactly(2))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 1);

        $processBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->setMethods(['setTimeout', 'add', 'getProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $processBuilder
            ->method('setTimeout')
            ->willReturnSelf();
        $processBuilder
            ->method('add')
            ->willReturnSelf();
        $processBuilder->expects($this->exactly(2))
            ->method('getProcess')
            ->willReturn($process);

        ProcessBuilder::addExpectedCall('create', [], $processBuilder);
        ProcessBuilder::addExpectedCall('create', [], $processBuilder);

        $this->bundle->boot();
    }

    public function testBuild()
    {
        CommandExecutor::addExpectedCall('isCurrentCommand', ['oro:entity-extend:cache:', true], true);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->exactly(8))
            ->method('addCompilerPass')
            ->withConsecutive(
                [$this->isInstanceOf(EntityExtendPass::class)],
                [$this->isInstanceOf(ConfigLoaderPass::class)],
                [$this->isInstanceOf(EntityManagerPass::class)],
                [$this->isInstanceOf(EntityMetadataBuilderPass::class)],
                [$this->isInstanceOf(MigrationConfigPass::class)],
                [$this->isInstanceOf(DoctrineOrmMappingsPass::class)],
                [$this->isInstanceOf(ExtensionPass::class)],
                [$this->isInstanceOf(WarmerPass::class)]
            )
            ->willReturn($this->returnSelf());

        $this->bundle->build($container);

        $this->assertStaticCalls(
            [
                'isCurrentCommand' => [
                    ['oro:entity-extend:cache:', true],
                ],
            ],
            [
                'registerClassLoader' => [
                    [null]
                ],
                'getEntityCacheDir' => [
                    [null]
                ],
                'getEntityNamespace' => [
                    []
                ],
            ],
            []
        );
    }

    /**
     * @param array $commandExecutor
     * @param array $extendClassLoadingUtils
     * @param array $processBuilder
     */
    private function assertStaticCalls(array $commandExecutor, array $extendClassLoadingUtils, array $processBuilder)
    {
        $this->assertEquals($commandExecutor, CommandExecutor::getCalls());
        $this->assertEquals($extendClassLoadingUtils, ExtendClassLoadingUtils::getCalls());
        $this->assertEquals($processBuilder, ProcessBuilder::getCalls());
    }

    private function clearStaticCalls()
    {
        CommandExecutor::clearCalls();
        ExtendClassLoadingUtils::clearCalls();
        ProcessBuilder::clearCalls();
    }
}
