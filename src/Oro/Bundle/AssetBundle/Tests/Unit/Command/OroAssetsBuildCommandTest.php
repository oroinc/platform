<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Command;

use Oro\Bundle\AssetBundle\AssetCommandProcessFactory;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\AssetBundle\Command\OroAssetsBuildCommand;
use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

class OroAssetsBuildCommandTest extends TestCase
{
    private AssetCommandProcessFactory|MockObject $assetCommandProcessFactory;
    private AssetConfigCache|MockObject $assetConfigCache;
    private ThemeManager|MockObject $themeManager;
    private OroAssetsBuildCommand $command;
    private Application|MockObject $application;
    private Kernel|MockObject $kernel;
    private string $projectDir;
    private Process|MockObject $process;

    #[\Override]
    protected function setUp(): void
    {
        $this->assetCommandProcessFactory = $this->createMock(AssetCommandProcessFactory::class);
        $this->assetConfigCache = $this->createMock(AssetConfigCache::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->application = $this->createMock(Application::class);
        $this->kernel = $this->createMock(Kernel::class);
        $this->process = $this->createMock(Process::class);

        $this->projectDir = sys_get_temp_dir();

        if (!is_dir($this->projectDir . '/node_modules')) {
            mkdir($this->projectDir . '/node_modules', 0777, true);
        }

        $this->application
            ->expects(self::any())
            ->method('getKernel')
            ->willReturn($this->kernel);

        $this->command = new OroAssetsBuildCommand(
            $this->assetCommandProcessFactory,
            $this->assetConfigCache,
            '/tmp/npm',
            30,
            30,
            true
        );

        $commandDefinition = new InputDefinition();

        $this->application->expects(self::once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $this->application->expects(self::any())
            ->method('getDefinition')
            ->willReturn($commandDefinition);

        $this->command->setApplication($this->application);
        $this->command->setDefinition($commandDefinition);
        $this->command->setThemeManager($this->themeManager);
    }

    public function testWebPackBuildOnEachThemeSeparately()
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = new OutputStub();

        $this->kernel->expects(self::any())
            ->method('getProjectDir')
            ->willReturn($this->projectDir);

        $this->kernel->expects(self::any())
            ->method('getCacheDir')
            ->willReturn('/tmp/cache');

        $this->assetConfigCache->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $inputMock->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['iterate-themes', true],
                ['env', 'prod'],
                ['no-debug', true],
            ]);

        $inputMock->expects(self::exactly(7))
            ->method('getArgument')
            ->with('theme')
            ->willReturnOnConsecutiveCalls(
                null,
                'admin.oro',
                'admin.oro',
                'default_50',
                'default_50',
                'default_51',
                'default_51',
            );

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->willReturn([
                new Theme('default_50'),
                new Theme('default_51')
            ]);

        $this->assetCommandProcessFactory->expects(self::exactly(3))
            ->method('create')
            ->willReturn($this->process);

        $this->process->expects(self::exactly(3))
            ->method('run')
            ->willReturn(11);

        $this->process->expects(self::exactly(3))
            ->method('isSuccessful')
            ->willReturn(true);

        $this->command->run($inputMock, $outputMock);
    }
}
