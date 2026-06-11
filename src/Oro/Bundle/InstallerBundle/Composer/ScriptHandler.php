<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Script handler for composer.
 * - installs npm assets
 * - sets permission on app directories
 * - execute oro scripts of dependent packages
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ScriptHandler
{
    private const string ORO_POST_INSTALL_CMD = 'oro-post-install-cmd';
    private const string ORO_POST_UPDATE_CMD = 'oro-post-update-cmd';

    private const string PACKAGE_JSON = 'package.json';
    private const string PNPM_LOCK = 'pnpm-lock.yaml';
    private const string PNPM_WORKSPACE = 'pnpm-workspace.yaml';

    /**
     * Working directory used when composer is invoked with the dev.json manifest.
     */
    private const string DEV_ASSETS_DIR = 'dev';
    private const string DEV_MANIFEST = 'dev.json';

    /**
     * @throws \Exception
     */
    public static function executePostInstallPackageScripts(Event $event): void
    {
        self::dispatchOroScripts($event, self::ORO_POST_INSTALL_CMD);
    }

    public static function executePostUpdatePackageScripts(Event $event): void
    {
        self::dispatchOroScripts($event, self::ORO_POST_UPDATE_CMD);
    }

    /**
     * Installs npm assets
     *
     * @param Event $event A instance
     * @throws \Exception
     */
    public static function installAssets(Event $event): void
    {
        $options = static::getOptions($event);
        $npmAssets = self::collectNpmAssets($event->getComposer());
        if (!$npmAssets) {
            return;
        }

        $isDev = self::isDevManifest($event);
        $pkgPath = $isDev ? self::DEV_ASSETS_DIR . '/' . self::PACKAGE_JSON : self::PACKAGE_JSON;
        $lockPath = $isDev ? self::DEV_ASSETS_DIR . '/' . self::PNPM_LOCK : self::PNPM_LOCK;
        $workDir = $isDev ? self::DEV_ASSETS_DIR : '';

        $filesystem = static::getFilesystem();
        $name = self::writeNpmPackageJson($filesystem, $pkgPath, $npmAssets);

        $isVerbose = $event->getIO()->isVerbose();
        $hasMonorepoWorkspace = $filesystem->exists('../../' . self::PNPM_WORKSPACE);

        if (!$filesystem->exists($lockPath) && !($isDev && $hasMonorepoWorkspace)) {
            self::pnpmInstall($event->getIO(), $options['process-timeout'], $isVerbose, $workDir);

            return;
        }

        if ($hasMonorepoWorkspace) {
            self::pnpmInstallMonorepo(
                $event->getIO(),
                $options['process-timeout'],
                $isVerbose,
                $name,
                $workDir
            );

            return;
        }

        // Installs assets using lock file.
        self::pnpmCi($event->getIO(), $options['process-timeout'], $isVerbose, $workDir);
    }

    private static function writeNpmPackageJson(Filesystem $filesystem, string $pkgPath, array $npmAssets): string
    {
        if ($filesystem->exists($pkgPath)) {
            $packageJson = static::getPackageJsonContent($pkgPath, false);
            $packageJson->dependencies = $npmAssets;
            $name = $packageJson->name ?? '';
        } else {
            // File package.json with actual dependencies is required for correct work of npm.
            $packageJson = [
                'description' =>
                    'THE FILE IS GENERATED PROGRAMMATICALLY, ALL MANUAL CHANGES IN DEPENDENCIES SECTION WILL BE LOST',
                'homepage' => 'https://doc.oroinc.com/master/frontend/javascript/composer-js-dependencies/',
                'dependencies' => $npmAssets,
                'private' => true,
            ];
            $name = '';
        }
        $filesystem
            ->dumpFile($pkgPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return $name;
    }

    /**
     * Updates pnpm assets
     *
     * @param Event $event A instance
     */
    public static function updateAssets(Event $event): void
    {
        $isDev = self::isDevManifest($event);
        $lockPath = $isDev ? self::DEV_ASSETS_DIR . '/' . self::PNPM_LOCK : self::PNPM_LOCK;

        static::getFilesystem()->remove($lockPath);

        static::installAssets($event);
    }

    /**
     * Detects whether composer was invoked with the dev.json manifest. Checks the COMPOSER
     * env var first (set by composer itself), then falls back to the loaded config source
     * for invocations that pass -f/--file without exporting the env var.
     */
    private static function isDevManifest(Event $event): bool
    {
        $envManifest = getenv('COMPOSER');
        if (is_string($envManifest) && $envManifest !== '') {
            return basename($envManifest) === self::DEV_MANIFEST;
        }

        $source = $event->getComposer()->getConfig()->getConfigSource()->getName();

        return basename($source) === self::DEV_MANIFEST;
    }

    /**
     * Set permissions for directories
     */
    public static function setPermissions(Event $event): void
    {
        $options = static::getOptions($event);

        $webDir = $options['symfony-web-dir'];

        $directories = [
            'var/cache',
            'var/logs',
            'var/data',
            $webDir,
        ];

        $permissionHandler = new PermissionsHandler();
        foreach ($directories as $directory) {
            $permissionHandler->setPermissions($directory);
        }
    }

    /**
     * Sets the global assets version.
     *
     * @param Event $event A instance
     */
    public static function setAssetsVersion(Event $event): void
    {
        $assetsVersion = substr(md5(date('c')), 0, 8);

        static::saveAssetsVersion($assetsVersion);
    }

    protected static function saveAssetsVersion(string $version): void
    {
        $filesystem = new Filesystem();
        $filePath = static::getAssetsVersionFile();
        $directoryPath = pathinfo($filePath, PATHINFO_DIRNAME);

        if (!is_dir($directoryPath)) {
            $filesystem->remove($directoryPath);
        }
        if (!file_exists($directoryPath)) {
            $filesystem->mkdir($directoryPath);
        }

        file_put_contents($filePath, $version);
    }

    protected static function getAssetsVersionFile(): string
    {
        return 'public/build/build_version.txt';
    }

    protected static function getOptions(Event $event): array
    {
        $composer = $event->getComposer();
        $config = $composer->getConfig();

        return array_merge(
            ['symfony-web-dir' => 'public'],
            $composer->getPackage()->getExtra(),
            [
                'process-timeout' => $config->get('process-timeout'),
                'vendor-dir' => $config->get('vendor-dir'),
            ]
        );
    }

    private static function dispatchOroScripts(Event $event, string $oroEvent): void
    {
        $rootPackage = $event->getComposer()->getPackage();
        $dispatcher = $event->getComposer()->getEventDispatcher();
        $packages = self::getInstalledPackages($event->getComposer());

        foreach ($packages as $package) {
            $oroScripts = $package->getExtra()[$oroEvent] ?? [];
            if (empty($oroScripts)) {
                continue;
            }

            self::collectOroScripts($rootPackage, $package, $oroScripts, $oroEvent);

            $dispatcher->dispatchScript($oroEvent, $event->isDevMode(), $event->getArguments(), $event->getFlags());
        }
    }

    private static function collectOroScripts(
        RootPackageInterface $rootPackage,
        PackageInterface $package,
        array $oroScripts,
        string $oroEvent
    ): void {
        $rootScripts = $rootPackage->getScripts();

        foreach ($oroScripts as $oroScript) {
            $oroScriptName = mb_substr($oroScript, 1);
            // add Oro scripts to the root composer script section
            if (isset($package->getScripts()[$oroScriptName]) && !isset($rootScripts[$oroScriptName])) {
                $rootScripts[$oroScriptName] = $package->getScripts()[$oroScriptName];
            }
        }

        // add calls for Oro scripts in the composer events section
        $rootEventScripts = $rootPackage->getScripts()[$oroEvent] ?? [];
        $diffScripts = array_diff($oroScripts, $rootEventScripts);
        $rootScripts[$oroEvent] = [...$rootEventScripts, ...$diffScripts];

        $rootPackage->setScripts($rootScripts);
    }

    /**
     * Collects npm assets from extra.npm section of installed packages.
     *
     * @throws \Exception
     */
    private static function collectNpmAssets(Composer $composer): array
    {
        $rootPackage = $composer->getPackage();

        // Gets array of installed packages.
        $packages = self::getInstalledPackages($composer);

        $npmAssets = [];
        $rootNpmAssets = $rootPackage->getExtra()['npm'] ?? [];
        if (!is_array($rootNpmAssets)) {
            $rootNpmAssets = [];
        }

        foreach ($packages as $package) {
            $packageNpm = $package->getExtra()['npm'] ?? [];

            if ($packageNpm && is_array($packageNpm) && !isset($npmAssets[$package->getName()])) {
                $conflictingPackages = array_diff_key(array_intersect_key($packageNpm, $npmAssets), $rootNpmAssets);

                if (!empty($conflictingPackages)) {
                    throw new \Exception(
                        'There are some conflicting npm packages "' .
                        implode('", "', array_keys($conflictingPackages)) . '". To resolve conflicts, see ' .
                        'https://doc.oroinc.com/master/frontend/javascript/composer-js-dependencies' .
                        '#resolving-conflicting-npm-dependencies/'
                    );
                }

                $npmAssets = array_merge($npmAssets, $packageNpm);
            }
        }

        $npmAssets = array_merge($npmAssets, $rootNpmAssets);
        ksort($npmAssets, SORT_STRING | SORT_FLAG_CASE);

        return $npmAssets;
    }

    /**
     * Seam: reads and decodes a package.json. Override in a test subclass to substitute
     * a stub without touching the filesystem.
     */
    protected static function getPackageJsonContent(string $filePath, bool $associative = true): array|\stdClass
    {
        $packageJsonContent = @file_get_contents($filePath);
        if ($packageJsonContent === false) {
            throw new \Exception(sprintf(
                'Cannot read the %s file; it may be missing or not readable',
                $filePath
            ));
        }
        try {
            $packageJson = json_decode($packageJsonContent, $associative, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            throw new \Exception(
                sprintf('Cannot parse the %s file; it does not contain valid JSON', $filePath),
                0,
                $exception
            );
        }

        return $packageJson;
    }

    /**
     * Runs "pnpm install" and "pnpm run build" in the monorepo root.
     *
     * In dev.json mode this is two-phase: (1) copy npm-package into dev/ and build those members
     * there in isolation (own node_modules, so vite & co. link correctly), then (2) install the
     * app with --dir dev + --modules-dir so node_modules lands in the app (webpack layout) and the
     * members link to the dev/npm-package copy. In composer.json mode it is a single install
     * followed by the sub-dependency build.
     */
    private static function pnpmInstallMonorepo(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false,
        string $packageName = '',
        string $workDir = ''
    ): void {
        $logLevel = $verbose ? 'info' : 'error';
        $monorepoRoot = dirname(getcwd(), 2);

        $devDir = getcwd() . DIRECTORY_SEPARATOR . $workDir;

        if ($packageName !== '' && $workDir !== '') {
            self::installDevNpmPackageMembers($inputOutput, $timeout, $logLevel, $packageName, $devDir, $monorepoRoot);
        }

        $pnpmInstallCmd = ['pnpm', 'install', '--prefer-offline', '--ignore-script', '--loglevel', $logLevel];

        if ($packageName !== '') {
            /**
             * When a package name is provided, pnpm install will target only
             * the specified application and it's dependencies recursively
             */
            $pnpmInstallCmd[] = '--filter';
            $pnpmInstallCmd[] = $packageName . '...';
        }

        if ($workDir !== '') {
            // dev.json phase 2: run the install as if started in dev/ (--dir) so --modules-dir
            // ../node_modules resolves inside the app (app/node_modules, the webpack layout)
            // rather than the repository parent, and pin the lockfile to dev/. codemirror & co.
            // link to the dev/npm-package copy built in phase 1.
            $pnpmInstallCmd[] = '--modules-dir';
            $pnpmInstallCmd[] = '../node_modules';
            $pnpmInstallCmd[] = '--lockfile-dir';
            $pnpmInstallCmd[] = $devDir;
            $pnpmInstallCmd[] = '--dir';
            $pnpmInstallCmd[] = $devDir;
        }

        if (static::runProcess($inputOutput, $pnpmInstallCmd, $timeout, $monorepoRoot) !== 0) {
            throw new \RuntimeException('Failed to install pnpm assets in monorepo');
        }

        if ($workDir !== '') {
            // dev.json: npm-package members were already built in phase 1.
            return;
        }

        self::buildApplicationSubDependencies($inputOutput, $timeout, $logLevel, $packageName, $monorepoRoot);
    }

    private static function buildApplicationSubDependencies(
        IOInterface $inputOutput,
        int $timeout,
        string $logLevel,
        string $packageName,
        string $monorepoRoot
    ): void {
        if ($packageName === '') {
            return;
        }

        $pnpmBuildDepsCmd = [
            'pnpm',
            '-r',
            '--loglevel',
            $logLevel,
            '--filter',
            $packageName . '^...',
            '--if-present',
            'run',
            'build',
        ];

        if (static::runProcess($inputOutput, $pnpmBuildDepsCmd, $timeout, $monorepoRoot) !== 0) {
            throw new \RuntimeException('Failed to build sub dependencies for application');
        }
    }

    private static function installDevNpmPackageMembers(
        IOInterface $inputOutput,
        int $timeout,
        string $logLevel,
        string $packageName,
        string $devDir,
        string $monorepoRoot
    ): void {
        $devNpmPackage = $devDir . DIRECTORY_SEPARATOR . 'npm-package';
        $filesystem = static::getFilesystem();
        $filesystem->remove($devNpmPackage);
        $filesystem->mirror($monorepoRoot . DIRECTORY_SEPARATOR . 'npm-package', $devNpmPackage);

        $membersInstallCmd = [
            'pnpm', 'install', '--prefer-offline', '--ignore-script', '--loglevel', $logLevel,
            '--filter', $packageName . '^...', '--dir', $devDir, '--lockfile-dir', $devDir,
        ];
        if (static::runProcess($inputOutput, $membersInstallCmd, $timeout, $monorepoRoot) !== 0) {
            throw new \RuntimeException('Failed to install npm-package members');
        }

        $membersBuildCmd = [
            'pnpm', '--dir', $devDir, '-r', '--loglevel', $logLevel,
            '--filter', $packageName . '^...', '--if-present', 'run', 'build',
        ];
        if (static::runProcess($inputOutput, $membersBuildCmd, $timeout, $monorepoRoot) !== 0) {
            throw new \RuntimeException('Failed to build npm-package members');
        }
    }

    /**
     * Runs "pnpm install", updates pnpm-lock.yaml, installs assets to "node_modules/".
     *
     * In dev.json mode ($workDir !== ''):
     * - --dir + --lockfile-dir redirect both the install scope and the lockfile into dev/;
     * - --modules-dir ../node_modules hoists node_modules into the composer cwd so the
     *   layout matches what oro:assets:build later validates against.
     */
    private static function pnpmInstall(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false,
        string $workDir = ''
    ): void {
        $logLevel = $verbose ? 'info' : 'error';
        $pnpmInstallCmd = [
            'pnpm', 'install',
            '--no-frozen-lockfile', '--ignore-script',
            '--loglevel', $logLevel,
        ];

        if ($workDir !== '') {
            $pnpmInstallCmd[] = '--dir';
            $pnpmInstallCmd[] = $workDir;
            $pnpmInstallCmd[] = '--lockfile-dir';
            $pnpmInstallCmd[] = $workDir;
            $pnpmInstallCmd[] = '--modules-dir';
            $pnpmInstallCmd[] = '../node_modules';
        }

        if (static::runProcess($inputOutput, $pnpmInstallCmd, $timeout) !== 0) {
            throw new \RuntimeException('Failed to generate pnpm-lock.yaml');
        }
    }

    /**
     * Seam: returns the Filesystem used by installAssets/updateAssets. Override in a test
     * subclass to substitute a mock — production callers always rely on the default.
     */
    protected static function getFilesystem(): Filesystem
    {
        return new Filesystem();
    }

    /**
     * Seam: invokes a pnpm command and returns the exit code. Override in a test subclass
     * to capture the command/cwd/timeout without spawning a real Process.
     */
    protected static function runProcess(
        IOInterface $inputOutput,
        array $cmd,
        int $timeout,
        ?string $cwd = null
    ): int {
        $inputOutput->write(implode(' ', $cmd));

        $process = new Process($cmd, $cwd, null, null, $timeout);
        $process->run(function ($outputType, string $data) use ($inputOutput) {
            if ($outputType === Process::OUT) {
                $inputOutput->write($data, false);
            } else {
                $inputOutput->writeError($data, false);
            }
        });

        return $process->getExitCode();
    }

    /**
     * Runs "pnpm install", installs assets to "node_modules/" using only pnpm-lock.yaml.
     *
     * In dev.json mode ($workDir !== ''):
     * - --dir + --lockfile-dir redirect both the install scope and the lockfile into dev/;
     * - --modules-dir ../node_modules hoists node_modules into the composer cwd so the
     *   layout matches what oro:assets:build later validates against.
     */
    private static function pnpmCi(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false,
        string $workDir = ''
    ): void {
        $logLevel = $verbose ? 'info' : 'error';
        $npmCiCmd = ['pnpm', 'install', '--frozen-lockfile', '--loglevel', $logLevel];

        if ($workDir !== '') {
            $npmCiCmd[] = '--dir';
            $npmCiCmd[] = $workDir;
            $npmCiCmd[] = '--lockfile-dir';
            $npmCiCmd[] = $workDir;
            $npmCiCmd[] = '--modules-dir';
            $npmCiCmd[] = '../node_modules';
        }

        if (static::runProcess($inputOutput, $npmCiCmd, $timeout) !== 0) {
            throw new \RuntimeException('Failed to install pnpm assets');
        }
    }

    /**
     * @return PackageInterface[]
     */
    private static function getInstalledPackages(Composer $composer): array
    {
        return $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
    }
}
