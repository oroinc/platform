<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Config\ConfigSourceInterface;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Oro\Bundle\InstallerBundle\Composer\ScriptHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Covers the public API surface that the dev.json refactor touches:
 * - installAssets (composer.json + dev.json variations)
 * - updateAssets (dev.json)
 * - isDevManifest detection (env / configSource)
 * - error propagation for each pnpm branch
 * - executePost*PackageScripts (collection of the oro scripts of the installed packages)
 */
class ScriptHandlerTest extends TestCase
{
    private const string ROOT_PKG_NAME = 'commerce-crm-ee';
    private const string DEV_PKG_NAME = 'commerce-crm-ee-dev';

    /** Extra.npm of the root composer package — keep small + sortable for assertions. */
    private const array NPM_ASSETS = ['lib-a' => '1.0.0', 'lib-b' => '2.0.0'];

    /** Cmd-prefix shared by every pnpm invocation; tests append the per-branch tail. */
    private const array PNPM_BASE = ['pnpm', 'install'];

    #[\Override]
    protected function setUp(): void
    {
        TestableScriptHandler::reset();
        putenv('COMPOSER');
    }

    #[\Override]
    protected function tearDown(): void
    {
        TestableScriptHandler::reset();
        putenv('COMPOSER');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // installAssets — composer.json
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider installAssetsComposerCases
     * @param list<list<string>> $expectedCmds
     */
    public function testInstallAssetsComposerJson(bool $pkgExists, bool $lockExists, array $expectedCmds): void
    {
        $this->stubExistingPackageJson($pkgExists, self::ROOT_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'package.json' => $pkgExists,
            'pnpm-lock.yaml' => $lockExists,
        ]);
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::ROOT_PKG_NAME)));
        $fs->expects(self::never())
            ->method('symlink');

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=composer.json');

        TestableScriptHandler::installAssets($this->makeEvent());

        $this->assertInvocations($expectedCmds);
    }

    public static function installAssetsComposerCases(): array
    {
        $logLevel = 'error';

        $frozen = [...self::PNPM_BASE, '--frozen-lockfile', '--loglevel', $logLevel];
        $noFrozen = [
            ...self::PNPM_BASE,
            '--no-frozen-lockfile', '--ignore-scripts',
            '--loglevel', $logLevel,
        ];

        return [
            'pkg + lock → pnpmCi' => [
                'pkgExists' => true,
                'lockExists' => true,
                'expectedCmds' => [$frozen],
            ],
            'pkg + no lock → pnpmInstall' => [
                'pkgExists' => true,
                'lockExists' => false,
                'expectedCmds' => [$noFrozen],
            ],
            'no pkg + lock → pnpmCi' => [
                'pkgExists' => false,
                'lockExists' => true,
                'expectedCmds' => [$frozen],
            ],
            'no pkg + no lock → pnpmInstall' => [
                'pkgExists' => false,
                'lockExists' => false,
                'expectedCmds' => [$noFrozen],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // installAssets — dev.json (covers branches that updateAssets cannot reach)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider installAssetsDevCases
     * @param list<list<string>> $expectedCmds
     */
    public function testInstallAssetsDevJson(bool $pkgExists, array $expectedCmds): void
    {
        $this->stubExistingPackageJson($pkgExists, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'dev/package.json' => $pkgExists,
        ]);
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('dev/package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::DEV_PKG_NAME)));
        $fs->expects(self::once())
            ->method('symlink')
            ->with('dev/node_modules', 'node_modules');
        $fs->expects(self::never())
            ->method('mirror');

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=dev.json');

        TestableScriptHandler::installAssets($this->makeEvent());

        $this->assertInvocations($expectedCmds);
    }

    public static function installAssetsDevCases(): array
    {
        $ll = 'error';

        $devInstall = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-scripts',
            '--loglevel',
            $ll,
            '--filter',
            self::DEV_PKG_NAME . '...',
            '--dir',
            'dev',
        ];
        $devMembersBuild = [
            'pnpm',
            '--dir',
            'dev',
            '-r',
            '--loglevel',
            $ll,
            '--filter',
            self::DEV_PKG_NAME . '^...',
            '--if-present',
            'run',
            'build',
        ];
        $devInstallNoName = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-scripts',
            '--loglevel',
            $ll,
            '--dir',
            'dev',
        ];

        return [
            'dev pkg → workspace install + members build' => [
                'pkgExists' => true,
                'expectedCmds' => [$devInstall, $devMembersBuild],
            ],
            'no dev pkg → workspace install without --filter, build skipped' => [
                'pkgExists' => false,
                'expectedCmds' => [$devInstallNoName],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // updateAssets — dev.json
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider updateAssetsDevCases
     * @param list<list<string>> $expectedCmds
     */
    public function testUpdateAssetsDevJson(bool $pkgExists, array $expectedCmds): void
    {
        $this->stubExistingPackageJson($pkgExists, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'dev/package.json' => $pkgExists,
        ]);
        $fs->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(['dev/pnpm-lock.yaml'], ['node_modules']);
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('dev/package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::DEV_PKG_NAME)));
        $fs->expects(self::once())
            ->method('symlink')
            ->with('dev/node_modules', 'node_modules');

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=dev.json');

        TestableScriptHandler::updateAssets($this->makeEvent());

        $this->assertInvocations($expectedCmds);
    }

    public static function updateAssetsDevCases(): array
    {
        return self::installAssetsDevCases();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // isDevManifest detection — covered indirectly via the dumpFile path chosen
    // ──────────────────────────────────────────────────────────────────────────

    /** @dataProvider manifestDetectionCases */
    public function testIsDevManifestDetectionViaInstallAssets(
        ?string $envValue,
        string $configSourceBaseName,
        string $expectedPkgPath,
    ): void {
        $fs = $this->makeFilesystemMock([
            $expectedPkgPath => false,
            self::lockPathFor($expectedPkgPath) => false,
        ]);
        $fs->expects(self::once())->method('dumpFile')->with($expectedPkgPath, self::anything());

        TestableScriptHandler::$filesystem = $fs;
        // putenv('COMPOSER=') equals "unset" as far as isDevManifest() is concerned —
        // production checks `is_string($envManifest) && $envManifest !== ''`.
        putenv('COMPOSER=' . ($envValue ?? ''));

        TestableScriptHandler::installAssets($this->makeEvent(configSource: '/abs/' . $configSourceBaseName));
    }

    public static function manifestDetectionCases(): array
    {
        return [
            'env=dev.json wins over configSource' => [
                'envValue' => 'dev.json',
                'configSourceBaseName' => 'composer.json',
                'expectedPkgPath' => 'dev/package.json',
            ],
            'env=composer.json wins over configSource' => [
                'envValue' => 'composer.json',
                'configSourceBaseName' => 'dev.json',
                'expectedPkgPath' => 'package.json',
            ],
            'no env, fallback to configSource=dev.json' => [
                'envValue' => null,
                'configSourceBaseName' => 'dev.json',
                'expectedPkgPath' => 'dev/package.json',
            ],
            'no env, fallback to configSource=composer.json' => [
                'envValue' => null,
                'configSourceBaseName' => 'composer.json',
                'expectedPkgPath' => 'package.json',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Error propagation for each pnpm branch
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider errorCases
     * @param list<int> $exitCodes
     */
    public function testErrorPropagation(
        string $manifest,
        bool $pkgExists,
        bool $lockExists,
        array $exitCodes,
        string $expectedMessage,
    ): void {
        $pkgPath = $manifest === 'dev.json' ? 'dev/package.json' : 'package.json';
        $this->stubExistingPackageJson($pkgExists, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            $pkgPath => $pkgExists,
            'pnpm-lock.yaml' => $lockExists,
        ]);
        $fs->method('dumpFile'); // allow but don't assert content here

        TestableScriptHandler::$filesystem = $fs;
        TestableScriptHandler::$exitCodes = $exitCodes;
        putenv('COMPOSER=' . $manifest);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        TestableScriptHandler::installAssets($this->makeEvent());
    }

    public static function errorCases(): array
    {
        return [
            'pnpmInstall fails (composer, no lock)' => [
                'manifest' => 'composer.json',
                'pkgExists' => false,
                'lockExists' => false,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to generate pnpm-lock.yaml',
            ],
            'pnpmCi fails (composer, lock)' => [
                'manifest' => 'composer.json',
                'pkgExists' => false,
                'lockExists' => true,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to install pnpm assets',
            ],
            'workspace install fails (dev)' => [
                'manifest' => 'dev.json',
                'pkgExists' => false,
                'lockExists' => false,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to install pnpm assets in monorepo',
            ],
            'members build fails (dev)' => [
                'manifest' => 'dev.json',
                'pkgExists' => true,
                'lockExists' => false,
                'exitCodes' => [0, 1],
                'expectedMessage' => 'Failed to build sub dependencies for application',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // process-timeout normalization
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider processTimeoutCases
     */
    public function testInstallAssetsNormalizesProcessTimeout(
        int|string $configuredTimeout,
        int $expectedTimeout
    ): void {
        $this->stubExistingPackageJson(true, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'dev/package.json' => true,
        ]);
        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=dev.json');

        TestableScriptHandler::installAssets($this->makeEvent('/abs/dev.json', $configuredTimeout));

        self::assertSame(
            array_fill(0, 2, $expectedTimeout),
            array_column(TestableScriptHandler::$invocations, 'timeout'),
            'process-timeout must reach every pnpm helper as an int'
        );
    }

    /**
     * 0 is not "zero seconds": Symfony Process treats it as no timeout at all.
     */
    public static function processTimeoutCases(): array
    {
        return [
            'int from composer config' => ['configuredTimeout' => 300, 'expectedTimeout' => 300],
            'string from COMPOSER_PROCESS_TIMEOUT' => ['configuredTimeout' => '600', 'expectedTimeout' => 600],
            'empty value -> no timeout' => ['configuredTimeout' => '', 'expectedTimeout' => 0],
            'negative value -> no timeout' => ['configuredTimeout' => '-5', 'expectedTimeout' => 0],
            'non-numeric value -> no timeout' => ['configuredTimeout' => 'ten', 'expectedTimeout' => 0],
            'digit-leading typo -> truncated' => ['configuredTimeout' => '6oo', 'expectedTimeout' => 6],
        ];
    }

    /**
     * @dataProvider packageScriptsEventDataProvider
     */
    public function testExecutePackageScriptsRunsCollectedScriptsOnce(string $method, string $oroEvent): void
    {
        $rootPackage = new RootPackage('oro/app', '1.0.0.0', '1.0.0');
        $rootPackage->setScripts(['post-install-cmd' => ['@execute-post-install-package-scripts']]);

        $first = new CompletePackage('oro/first', '1.0.0.0', '1.0.0');
        $first->setExtra([$oroEvent => ['@first-script']]);
        $first->setScripts(['first-script' => ['First\\Handler::run']]);

        $second = new CompletePackage('oro/second', '1.0.0.0', '1.0.0');
        $second->setExtra([$oroEvent => ['console second:command']]);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatchScript')
            ->with($oroEvent, false, [], [])
            ->willReturnCallback(static function () use ($rootPackage, $oroEvent): int {
                self::assertEquals(
                    [
                        'post-install-cmd' => ['@execute-post-install-package-scripts'],
                        'first-script' => ['First\\Handler::run'],
                        $oroEvent => ['@first-script', 'console second:command'],
                    ],
                    $rootPackage->getScripts()
                );

                return 0;
            });

        $event = $this->makePackageScriptsEvent(
            $rootPackage,
            [$first, new CompletePackage('oro/no-scripts', '1.0.0.0', '1.0.0'), $second],
            $dispatcher
        );
        ScriptHandler::{$method}($event);
    }

    /**
     * @dataProvider packageScriptsEventDataProvider
     */
    public function testExecutePackageScriptsDoesNothingWithoutPackageScripts(string $method): void
    {
        $rootPackage = new RootPackage('oro/app', '1.0.0.0', '1.0.0');

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects(self::never())
            ->method('dispatchScript');

        $event = $this->makePackageScriptsEvent(
            $rootPackage,
            [new CompletePackage('oro/no-scripts', '1.0.0.0', '1.0.0')],
            $dispatcher
        );
        ScriptHandler::{$method}($event);

        self::assertEquals([], $rootPackage->getScripts());
    }

    public function packageScriptsEventDataProvider(): array
    {
        return [
            'post install' => [
                'method' => 'executePostInstallPackageScripts',
                'oroEvent' => 'oro-post-install-cmd',
            ],
            'post update' => [
                'method' => 'executePostUpdatePackageScripts',
                'oroEvent' => 'oro-post-update-cmd',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @param PackageInterface[] $packages
     */
    private function makePackageScriptsEvent(
        RootPackage $rootPackage,
        array $packages,
        EventDispatcher $dispatcher
    ): Event {
        $repo = $this->createMock(InstalledRepositoryInterface::class);
        $repo->method('getCanonicalPackages')->willReturn($packages);

        $rm = $this->createMock(RepositoryManager::class);
        $rm->method('getLocalRepository')->willReturn($repo);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')->willReturn($rootPackage);
        $composer->method('getRepositoryManager')->willReturn($rm);
        $composer->method('getEventDispatcher')->willReturn($dispatcher);

        $event = $this->createMock(Event::class);
        $event->method('getComposer')->willReturn($composer);

        return $event;
    }

    private function makeEvent(string $configSource = '/abs/composer.json', int|string $processTimeout = 300): Event
    {
        $cfgSrc = $this->createMock(ConfigSourceInterface::class);
        $cfgSrc->method('getName')->willReturn($configSource);

        $cfg = $this->createMock(Config::class);
        $cfg->method('getConfigSource')->willReturn($cfgSrc);
        $cfg->method('get')->willReturnCallback(static fn (string $k): mixed => match ($k) {
            'process-timeout' => $processTimeout,
            'vendor-dir' => 'vendor',
            default => null,
        });

        $pkg = $this->createMock(RootPackageInterface::class);
        $pkg->method('getExtra')->willReturn(['npm' => self::NPM_ASSETS]);

        $repo = $this->createMock(InstalledRepositoryInterface::class);
        $repo->method('getCanonicalPackages')->willReturn([]);

        $rm = $this->createMock(RepositoryManager::class);
        $rm->method('getLocalRepository')->willReturn($repo);

        $composer = $this->createMock(Composer::class);
        $composer->method('getConfig')->willReturn($cfg);
        $composer->method('getPackage')->willReturn($pkg);
        $composer->method('getRepositoryManager')->willReturn($rm);

        $io = $this->createMock(IOInterface::class);
        $io->method('isVerbose')->willReturn(false);

        $event = $this->createMock(Event::class);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        return $event;
    }

    /** @param array<string,bool> $existsMap */
    private function makeFilesystemMock(array $existsMap): Filesystem&MockObject
    {
        $map = [];
        foreach ($existsMap as $path => $exists) {
            $map[] = [$path, $exists];
        }

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturnMap($map);

        return $fs;
    }

    /**
     * For tests that mock `fs->exists($pkgPath) → true`, install the stub object that
     * {@see TestableScriptHandler::getPackageJsonContent()} will return — no disk I/O.
     */
    private function stubExistingPackageJson(bool $pkgExists, string $name): void
    {
        if ($pkgExists) {
            TestableScriptHandler::$packageJson = (object)['name' => $name, 'dependencies' => []];
        }
    }

    /** @return callable(string): bool */
    private function assertPackageJsonPayload(bool $pkgExisted, string $expectedName): callable
    {
        return function (string $json) use ($pkgExisted, $expectedName): bool {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            self::assertSame(self::NPM_ASSETS, $decoded['dependencies'] ?? null);
            if ($pkgExisted) {
                self::assertSame($expectedName, $decoded['name'] ?? null);
            } else {
                self::assertArrayHasKey('description', $decoded);
                self::assertArrayHasKey('homepage', $decoded);
                self::assertTrue($decoded['private'] ?? false);
            }

            return true;
        };
    }

    /**
     * @param list<list<string>> $expectedCmds
     */
    private function assertInvocations(array $expectedCmds): void
    {
        $actualCmds = array_column(TestableScriptHandler::$invocations, 'cmd');
        $actualCwds = array_column(TestableScriptHandler::$invocations, 'cwd');
        self::assertSame($expectedCmds, $actualCmds, 'pnpm command lines mismatch');
        self::assertSame(array_fill(0, count($expectedCmds), null), $actualCwds, 'pnpm cwds mismatch');
    }

    private static function lockPathFor(string $pkgPath): string
    {
        return str_replace('package.json', 'pnpm-lock.yaml', $pkgPath);
    }
}

/**
 * Test-only subclass that overrides the protected seams in {@see ScriptHandler}.
 * Production code reaches the overrides via Late Static Binding (`static::`).
 *
 * - `runProcess` records each invocation and returns a deterministic exit code.
 * - `getFilesystem` returns the Filesystem instance set via {@see $filesystem}.
 * - `getPackageJsonContent` returns the stdClass stub set via {@see $packageJson}.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class TestableScriptHandler extends ScriptHandler
{
    /** @var list<array{cmd: list<string>, cwd: ?string, timeout: int}> */
    public static array $invocations = [];

    /** @var list<int> exit codes for n-th invocation; missing entries default to 0. */
    public static array $exitCodes = [];

    public static ?Filesystem $filesystem = null;

    /** Stub returned from {@see getPackageJsonContent()}. */
    public static ?\stdClass $packageJson = null;

    public static function reset(): void
    {
        self::$invocations = [];
        self::$exitCodes = [];
        self::$filesystem = null;
        self::$packageJson = null;
    }

    protected static function getFilesystem(): Filesystem
    {
        return self::$filesystem ?? throw new \LogicException(
            'TestableScriptHandler::$filesystem must be set before invoking ScriptHandler.'
        );
    }

    protected static function runProcess(
        IOInterface $inputOutput,
        array $cmd,
        int $timeout,
        ?string $cwd = null
    ): int {
        $index = count(self::$invocations);
        self::$invocations[] = ['cmd' => $cmd, 'cwd' => $cwd, 'timeout' => $timeout];

        return self::$exitCodes[$index] ?? 0;
    }

    protected static function getPackageJsonContent(string $filePath, bool $associative = true): array|\stdClass
    {
        return self::$packageJson ?? throw new \LogicException(
            'TestableScriptHandler::$packageJson must be set when the production code is '
            . 'expected to read an existing package.json.'
        );
    }
}
