<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Config\ConfigSourceInterface;
use Composer\IO\IOInterface;
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
 *
 * The cwd placeholder convention used by dataProviders:
 *   'MONOREPO_ROOT' → $this->monorepoRoot (parent of parent of project cwd)
 *   null            → null (pnpm inherits cwd from the composer process)
 */
class ScriptHandlerTest extends TestCase
{
    private const string ROOT_PKG_NAME = 'commerce-crm-ee';
    private const string DEV_PKG_NAME = 'commerce-crm-ee-dev';

    /** Extra.npm of the root composer package — keep small + sortable for assertions. */
    private const array NPM_ASSETS = ['lib-a' => '1.0.0', 'lib-b' => '2.0.0'];

    /** Cmd-prefix shared by every pnpm invocation; tests append the per-branch tail. */
    private const array PNPM_BASE = ['pnpm', 'install'];

    private string $monorepoRoot;
    private string $projectDir;

    #[\Override]
    protected function setUp(): void
    {
        // Whatever the actual cwd is at test time — production reads it via the same
        // getcwd() call inside pnpmInstallMonorepo, so the values are guaranteed to
        // line up. No chdir/mkdir needed; the package.json / lock paths are mocked.
        $this->projectDir = (string) getcwd();
        $this->monorepoRoot = dirname($this->projectDir, 2);

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
     * @param list<string|null> $expectedCwdTokens
     */
    public function testInstallAssetsComposerJson(
        bool $pkgExists,
        bool $lockExists,
        bool $wsExists,
        array $expectedCmds,
        array $expectedCwdTokens,
    ): void {
        $this->stubExistingPackageJson($pkgExists, self::ROOT_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'package.json' => $pkgExists,
            'pnpm-lock.yaml' => $lockExists,
            '../../pnpm-workspace.yaml' => $wsExists,
        ]);
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::ROOT_PKG_NAME)));

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=composer.json');

        TestableScriptHandler::installAssets($this->makeEvent());

        $this->assertInvocations($expectedCmds, $expectedCwdTokens);
    }

    public static function installAssetsComposerCases(): array
    {
        $logLevel = 'error';

        $monorepoInstall = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-script',
            '--loglevel',
            $logLevel,
            '--filter',
            self::ROOT_PKG_NAME . '...',
        ];
        $monorepoBuild = [
            'pnpm',
            '-r',
            '--loglevel',
            $logLevel,
            '--filter',
            self::ROOT_PKG_NAME . '^...',
            '--if-present',
            'run',
            'build',
        ];
        $monorepoNoName = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-script',
            '--loglevel',
            $logLevel,
        ]; // no --filter when packageJson was created fresh (no `name`)
        $frozen = [...self::PNPM_BASE, '--frozen-lockfile', '--loglevel', $logLevel];
        $noFrozen = [
            ...self::PNPM_BASE,
            '--no-frozen-lockfile', '--ignore-script',
            '--loglevel', $logLevel,
        ];

        return [
            'pkg + lock + ws → monorepo install + build' => [
                'pkgExists' => true,
                'lockExists' => true,
                'wsExists' => true,
                'expectedCmds' => [$monorepoInstall, $monorepoBuild],
                'expectedCwdTokens' => ['MONOREPO_ROOT', 'MONOREPO_ROOT'],
            ],
            'pkg + lock + no ws → pnpmCi' => [
                'pkgExists' => true,
                'lockExists' => true,
                'wsExists' => false,
                'expectedCmds' => [$frozen],
                'expectedCwdTokens' => [null],
            ],
            'pkg + no lock → pnpmInstall' => [
                'pkgExists' => true,
                'lockExists' => false,
                'wsExists' => false,
                'expectedCmds' => [$noFrozen],
                'expectedCwdTokens' => [null],
            ],
            // prod safety: a missing lock is generated with the plain install even when the
            // monorepo workspace is present — only the dev manifest diverts generation to the
            // monorepo path, so composer.json behaviour is unchanged.
            'pkg + no lock + ws → pnpmInstall (prod: ws does not divert generation)' => [
                'pkgExists' => true,
                'lockExists' => false,
                'wsExists' => true,
                'expectedCmds' => [$noFrozen],
                'expectedCwdTokens' => [null],
            ],
            'no pkg + lock + ws → monorepo install only (no name → no filter, build skipped)' => [
                'pkgExists' => false,
                'lockExists' => true,
                'wsExists' => true,
                'expectedCmds' => [$monorepoNoName],
                'expectedCwdTokens' => ['MONOREPO_ROOT'],
            ],
            'no pkg + lock + no ws → pnpmCi' => [
                'pkgExists' => false,
                'lockExists' => true,
                'wsExists' => false,
                'expectedCmds' => [$frozen],
                'expectedCwdTokens' => [null],
            ],
            'no pkg + no lock → pnpmInstall' => [
                'pkgExists' => false,
                'lockExists' => false,
                'wsExists' => false,
                'expectedCmds' => [$noFrozen],
                'expectedCwdTokens' => [null],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // installAssets — dev.json (covers branches that updateAssets cannot reach)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider installAssetsDevCases
     * @param list<list<string>> $expectedCmds
     * @param list<string|null> $expectedCwdTokens
     */
    public function testInstallAssetsDevJson(
        bool $pkgExists,
        bool $lockExists,
        bool $wsExists,
        array $expectedCmds,
        array $expectedCwdTokens,
    ): void {
        $this->stubExistingPackageJson($pkgExists, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'dev/package.json' => $pkgExists,
            'dev/pnpm-lock.yaml' => $lockExists,
            '../../pnpm-workspace.yaml' => $wsExists,
        ]);
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('dev/package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::DEV_PKG_NAME)));

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=dev.json');

        TestableScriptHandler::installAssets($this->makeEvent());

        $this->assertInvocations($expectedCmds, $expectedCwdTokens);
    }

    public static function installAssetsDevCases(): array
    {
        $ll = 'error';
        $devLockDir = 'PROJECT_DEV';  // resolved to $this->projectDir . '/dev' at runtime

        // pnpmInstallMonorepo for dev — phase 1: install the dev/npm-package copy's members
        // (no --modules-dir). The copy itself is made via Filesystem (not a recorded process).
        $monorepoDevMembersInstall = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-script',
            '--loglevel',
            $ll,
            '--filter',
            self::DEV_PKG_NAME . '^...',
            '--dir',
            $devLockDir,
            '--lockfile-dir',
            $devLockDir,
        ];
        // phase 1: build the copied npm-package members from source (in dev/)
        $monorepoDevMembersBuild = [
            'pnpm',
            '--dir',
            $devLockDir,
            '-r',
            '--loglevel',
            $ll,
            '--filter',
            self::DEV_PKG_NAME . '^...',
            '--if-present',
            'run',
            'build',
        ];
        // phase 2: app install with the webpack layout (--modules-dir) and --dir dev
        $monorepoDev = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-script',
            '--loglevel',
            $ll,
            '--filter',
            self::DEV_PKG_NAME . '...',
            '--modules-dir',
            '../node_modules',
            '--lockfile-dir',
            $devLockDir,
            '--dir',
            $devLockDir,
        ];
        // dev-no-pkg → minimal pkg (no name) → no --filter, build skipped
        $monorepoDevNoName = [
            ...self::PNPM_BASE,
            '--prefer-offline',
            '--ignore-script',
            '--loglevel',
            $ll,
            '--modules-dir',
            '../node_modules',
            '--lockfile-dir',
            $devLockDir,
            '--dir',
            $devLockDir,
        ];
        // pnpmCi for dev: --frozen-lockfile --dir dev --lockfile-dir dev --modules-dir ../node_modules
        $frozenDev = [
            ...self::PNPM_BASE,
            '--frozen-lockfile',
            '--loglevel',
            $ll,
            '--dir',
            'dev',
            '--lockfile-dir',
            'dev',
            '--modules-dir',
            '../node_modules',
        ];
        // pnpmInstall for dev: --no-frozen-lockfile --ignore-script + dir/lockfile/modules
        $noFrozenDev = [
            ...self::PNPM_BASE,
            '--no-frozen-lockfile',
            '--ignore-script',
            '--loglevel',
            $ll,
            '--dir',
            'dev',
            '--lockfile-dir',
            'dev',
            '--modules-dir',
            '../node_modules',
        ];

        return [
            'dev pkg + lock + ws → monorepo members install + build + app install' => [
                'pkgExists' => true,
                'lockExists' => true,
                'wsExists' => true,
                'expectedCmds' => [$monorepoDevMembersInstall, $monorepoDevMembersBuild, $monorepoDev],
                'expectedCwdTokens' => ['MONOREPO_ROOT', 'MONOREPO_ROOT', 'MONOREPO_ROOT'],
            ],
            'dev pkg + lock + no ws → pnpmCi(dev)' => [
                'pkgExists' => true,
                'lockExists' => true,
                'wsExists' => false,
                'expectedCmds' => [$frozenDev],
                'expectedCwdTokens' => [null],
            ],
            'dev pkg + no lock → pnpmInstall(dev)' => [
                'pkgExists' => true,
                'lockExists' => false,
                'wsExists' => false,
                'expectedCmds' => [$noFrozenDev],
                'expectedCwdTokens' => [null],
            ],
            // The fix: with the monorepo workspace present, a missing dev lock is generated
            // through the SAME copy/two-phase pipeline that the frozen install later consumes,
            // so the generated lock carries the workspace importers and frozen does not fail.
            'dev pkg + no lock + ws → monorepo generates lock (gen == consume)' => [
                'pkgExists' => true,
                'lockExists' => false,
                'wsExists' => true,
                'expectedCmds' => [$monorepoDevMembersInstall, $monorepoDevMembersBuild, $monorepoDev],
                'expectedCwdTokens' => ['MONOREPO_ROOT', 'MONOREPO_ROOT', 'MONOREPO_ROOT'],
            ],
            'no dev pkg + lock + ws → monorepo without --filter' => [
                'pkgExists' => false,
                'lockExists' => true,
                'wsExists' => true,
                'expectedCmds' => [$monorepoDevNoName],
                'expectedCwdTokens' => ['MONOREPO_ROOT'],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // updateAssets — dev.json (swap of installAssets fork-1)
    // ──────────────────────────────────────────────────────────────────────────

    /** @dataProvider updateAssetsDevCases */
    public function testUpdateAssetsDevJson(bool $pkgExists): void
    {
        $this->stubExistingPackageJson($pkgExists, self::DEV_PKG_NAME);

        $fs = $this->makeFilesystemMock([
            'dev/package.json' => $pkgExists,
            'dev/pnpm-lock.yaml' => false, // after remove() it is always missing
            '../../pnpm-workspace.yaml' => false,
        ]);
        $fs->expects(self::once())->method('remove')->with('dev/pnpm-lock.yaml');
        $fs->expects(self::once())
            ->method('dumpFile')
            ->with('dev/package.json', self::callback($this->assertPackageJsonPayload($pkgExists, self::DEV_PKG_NAME)));

        TestableScriptHandler::$filesystem = $fs;
        putenv('COMPOSER=dev.json');

        TestableScriptHandler::updateAssets($this->makeEvent());

        // After remove → installAssets always lands on pnpmInstall(dev) branch.
        $this->assertInvocations(
            [
                [
                    ...self::PNPM_BASE,
                    '--no-frozen-lockfile',
                    '--ignore-script',
                    '--loglevel',
                    'error',
                    '--dir',
                    'dev',
                    '--lockfile-dir',
                    'dev',
                    '--modules-dir',
                    '../node_modules',
                ],
            ],
            [null],
        );
    }

    public static function updateAssetsDevCases(): array
    {
        return [
            'dev pkg exists' => ['pkgExists' => true],
            'dev pkg missing → minimal created' => ['pkgExists' => false],
        ];
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
            '../../pnpm-workspace.yaml' => false,
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
        bool $lockExists,
        bool $wsExists,
        array $exitCodes,
        string $expectedMessage,
    ): void {
        $pkgPath = $manifest === 'dev.json' ? 'dev/package.json' : 'package.json';
        $lockPath = self::lockPathFor($pkgPath);

        $fs = $this->makeFilesystemMock([
            $pkgPath => false,  // skip pkg branch; minimal created
            $lockPath => $lockExists,
            '../../pnpm-workspace.yaml' => $wsExists,
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
                'lockExists' => false,
                'wsExists' => false,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to generate pnpm-lock.yaml',
            ],
            'pnpmInstallMonorepo install fails (composer, lock + ws)' => [
                'manifest' => 'composer.json',
                'lockExists' => true,
                'wsExists' => true,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to install pnpm assets in monorepo',
            ],
            // The build step in pnpmInstallMonorepo runs whenever packageJson->name != '' (both modes).
            // Here pkg is missing → minimal pkg has no name → no build call. So we cannot trigger
            // 'Failed to build sub dependencies for application' from this test setup. Skipped.
            'pnpmCi fails (composer, lock, no ws)' => [
                'manifest' => 'composer.json',
                'lockExists' => true,
                'wsExists' => false,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to install pnpm assets',
            ],
            'pnpmInstall fails (dev, no lock)' => [
                'manifest' => 'dev.json',
                'lockExists' => false,
                'wsExists' => false,
                'exitCodes' => [1],
                'expectedMessage' => 'Failed to generate pnpm-lock.yaml',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function makeEvent(string $configSource = '/abs/composer.json'): Event
    {
        $cfgSrc = $this->createMock(ConfigSourceInterface::class);
        $cfgSrc->method('getName')->willReturn($configSource);

        $cfg = $this->createMock(Config::class);
        $cfg->method('getConfigSource')->willReturn($cfgSrc);
        $cfg->method('get')->willReturnCallback(static fn (string $k): mixed => match ($k) {
            'process-timeout' => 300,
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
     * @param list<string|null>  $expectedCwds
     */
    private function assertInvocations(array $expectedCmds, array $expectedCwds): void
    {
        $expectedCmds = array_map(fn (array $cmd) => $this->resolveTokens($cmd), $expectedCmds);
        $expectedCwds = $this->resolveTokens($expectedCwds);

        $actualCmds = array_column(TestableScriptHandler::$invocations, 'cmd');
        $actualCwds = array_column(TestableScriptHandler::$invocations, 'cwd');
        self::assertSame($expectedCmds, $actualCmds, 'pnpm command lines mismatch');
        self::assertSame($expectedCwds, $actualCwds, 'pnpm cwds mismatch');
    }

    /**
     * Substitutes placeholder tokens (used by dataProviders since they cannot know the
     * temp dir at provider time) for their runtime values. null is passed through.
     * @template T of string|null
     * @param list<T> $items
     * @return list<T>
     */
    private function resolveTokens(array $items): array
    {
        $resolver = [
            'MONOREPO_ROOT' => $this->monorepoRoot,
            'PROJECT_DEV' => $this->projectDir . '/dev',
        ];

        return array_map(static fn ($v) => is_string($v) && isset($resolver[$v]) ? $resolver[$v] : $v, $items);
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
