<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Command;

use Oro\Bundle\AssetBundle\AssetCommandProcessFactory;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\AssetBundle\Command\OroAssetsBuildCommand;
use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

class OroAssetsBuildCommandTest extends TestCase
{
    use TempDirExtension;

    private AssetCommandProcessFactory&MockObject $assetCommandProcessFactory;
    private AssetConfigCache&MockObject $assetConfigCache;
    private ThemeManager&MockObject $themeManager;
    private Kernel&MockObject $kernel;
    private string $projectDir;
    private Process&MockObject $process;
    private OroAssetsBuildCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->assetCommandProcessFactory = $this->createMock(AssetCommandProcessFactory::class);
        $this->assetConfigCache = $this->createMock(AssetConfigCache::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->kernel = $this->createMock(Kernel::class);
        $this->process = $this->createMock(Process::class);

        $this->projectDir = $this->getTempDir('assets_build_command');
        mkdir($this->projectDir . '/node_modules', 0777, true);

        $application = $this->createMock(Application::class);
        $application->expects(self::any())
            ->method('getKernel')
            ->willReturn($this->kernel);

        $this->command = new OroAssetsBuildCommand(
            $this->assetCommandProcessFactory,
            $this->assetConfigCache,
            '/tmp/npm',
            30,
            30,
            true,
            $this->themeManager
        );
        $this->command->setApplication($application);
    }

    public function testWebPackBuildOnEachThemeSeparately(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = new OutputStub();

        $this->kernel->expects(self::any())
            ->method('getProjectDir')
            ->willReturn($this->projectDir);

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

    /**
     * @dataProvider statsPresetDataProvider
     */
    public function testBuildCommandStatsPreset(string|false $envValue, int $verbosity, ?string $expectedStats): void
    {
        $this->withStatsEnv($envValue, function () use ($verbosity, $expectedStats) {
            $command = $this->buildCommand($verbosity);

            $statsArguments = array_values(array_filter(
                $command,
                static fn (string $argument) => str_starts_with($argument, 'stats=')
            ));
            self::assertSame(null === $expectedStats ? [] : ['stats=' . $expectedStats], $statsArguments);
        });
    }

    public function statsPresetDataProvider(): array
    {
        return [
            'verbosity without the variable' => [false, OutputInterface::VERBOSITY_DEBUG, 'verbose'],
            'empty variable keeps the verbosity' => ['', OutputInterface::VERBOSITY_DEBUG, 'verbose'],
            'variable overrides the verbosity' => ['normal', OutputInterface::VERBOSITY_DEBUG, 'normal'],
            'variable without verbosity' => [' minimal ', OutputInterface::VERBOSITY_NORMAL, 'minimal'],
            'variable turns the stats off' => ['none', OutputInterface::VERBOSITY_DEBUG, 'none'],
            'normal verbosity without the variable' => [false, OutputInterface::VERBOSITY_NORMAL, null],
        ];
    }

    public function testBuildCommandWithInvalidStatsPreset(): void
    {
        $this->withStatsEnv('noisy', function () {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('ORO_WEBPACK_STATS="noisy" is not a webpack stats preset.');

            $this->buildCommand(OutputInterface::VERBOSITY_NORMAL);
        });
    }

    private function buildCommand(int $verbosity): array
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['hot', false],
                ['no-debug', true],
                ['env', 'prod'],
            ]);
        $output = new OutputStub();
        $output->setVerbosity($verbosity);

        return ReflectionUtil::callMethod($this->command, 'buildCommand', [$input, $output]);
    }

    private function withStatsEnv(string|false $value, callable $callback): void
    {
        $previous = getenv('ORO_WEBPACK_STATS');
        putenv(false === $value ? 'ORO_WEBPACK_STATS' : 'ORO_WEBPACK_STATS=' . $value);
        try {
            $callback();
        } finally {
            putenv(false === $previous ? 'ORO_WEBPACK_STATS' : 'ORO_WEBPACK_STATS=' . $previous);
        }
    }
}
