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

        if ($filesystem->exists($pkgPath)) {
            $packageJson = static::getPackageJsonContent($pkgPath, false);
            $packageJson->dependencies = $npmAssets;
        } else {
            // File package.json with actual dependencies is required for correct work of npm.
            $packageJson = [
                'description' =>
                    'THE FILE IS GENERATED PROGRAMMATICALLY, ALL MANUAL CHANGES IN DEPENDENCIES SECTION WILL BE LOST',
                'homepage' => 'https://doc.oroinc.com/master/frontend/javascript/composer-js-dependencies/',
                'dependencies' => $npmAssets,
                'private' => true,
            ];
        }
        $filesystem
            ->dumpFile($pkgPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $isVerbose = $event->getIO()->isVerbose();
        if (!$filesystem->exists($lockPath)) {
            // Creates lock file, installs assets.
            self::pnpmInstall($event->getIO(), $options['process-timeout'], $isVerbose, $workDir);

            return;
        }

        if ($filesystem->exists('../../' . self::PNPM_WORKSPACE)) {
            // In case of monorepo we need to run pnpm install in the monorepo root
            $name = is_object($packageJson) ? ($packageJson->name ?? '') : ($packageJson['name'] ?? '');
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
     * In dev.json mode the install uses --modules-dir to keep the node_modules layout webpack
     * expects, but that layout skips the npm-package members' build devDependencies — so the
     * build is preceded by a second, --modules-dir-free install of those members.
     */
    private static function pnpmInstallMonorepo(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false,
        string $packageName = '',
        string $workDir = ''
    ): void {
        $logLevel = $verbose ? 'info' : 'error';

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
            // dev.json: hoist node_modules to the composer cwd (webpack layout) and pin the
            // lockfile to dev/.
            $pnpmInstallCmd[] = '--modules-dir';
            $pnpmInstallCmd[] = '../node_modules';
            $pnpmInstallCmd[] = '--lockfile-dir';
            $pnpmInstallCmd[] = getcwd() . DIRECTORY_SEPARATOR . $workDir;
        }

        $monorepoRoot = dirname(getcwd(), 2);
        if (static::runProcess($inputOutput, $pnpmInstallCmd, $timeout, $monorepoRoot) !== 0) {
            throw new \RuntimeException('Failed to install pnpm assets in monorepo');
        }

        if ($packageName !== '') {
            if ($workDir !== '') {
                // dev.json: re-install the workspace deps without --modules-dir so the
                // npm-package members get their own node_modules (incl. build tooling like vite).
                $depsInstallCmd = [
                    'pnpm',
                    'install',
                    '--prefer-offline',
                    '--ignore-script',
                    '--loglevel',
                    $logLevel,
                    '--filter',
                    $packageName . '^...',
                    '--lockfile-dir',
                    getcwd() . DIRECTORY_SEPARATOR . $workDir,
                ];

                if (static::runProcess($inputOutput, $depsInstallCmd, $timeout, $monorepoRoot) !== 0) {
                    throw new \RuntimeException('Failed to install build dependencies for npm-package members');
                }
            }

            /**
             * When a package name is provided, this command builds only the dependencies
             * of the specified application, excluding the application itself
             */
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
