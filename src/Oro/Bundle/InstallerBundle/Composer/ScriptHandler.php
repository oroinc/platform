<?php

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
 */
class ScriptHandler
{
    private const string ORO_POST_INSTALL_CMD = 'oro-post-install-cmd';
    private const string ORO_POST_UPDATE_CMD  = 'oro-post-update-cmd';

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
        $options = self::getOptions($event);
        $npmAssets = self::collectNpmAssets($event->getComposer());
        if (!$npmAssets) {
            return;
        }

        $filesystem = new Filesystem();

        if ($filesystem->exists('package.json')) {
            $packageJson = self::getPackageJsonContent('package.json', false);
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
            ->dumpFile('package.json', json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $isVerbose = $event->getIO()->isVerbose();
        if (!$filesystem->exists('pnpm-lock.yaml')) {
            // Creates lock file, installs assets.
            self::pnpmInstall($event->getIO(), $options['process-timeout'], $isVerbose);
        } else {
            // Installs assets using lock file.
            self::pnpmCi($event->getIO(), $options['process-timeout'], $isVerbose);
        }
    }

    /**
     * Updates pnpm assets
     *
     * @param Event $event A instance
     */
    public static function updateAssets(Event $event): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove('pnpm-lock.yaml');

        self::installAssets($event);
    }

    /**
     * Set permissions for directories
     */
    public static function setPermissions(Event $event): void
    {
        $options = self::getOptions($event);

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

        self::saveAssetsVersion($assetsVersion);
    }

    protected static function saveAssetsVersion(string $version): void
    {
        $filesystem    = new Filesystem();
        $filePath      = self::getAssetsVersionFile();
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
                    throw new \Exception('Where are some conflicting npm packages "' .
                        implode('", "', array_keys($conflictingPackages)) . '". To how resolve conflicts, see ' .
                        'https://doc.oroinc.com/master/frontend/javascript/composer-js-dependencies' .
                        '#resolving-conflicting-npm-dependencies/');
                }

                $npmAssets = array_merge($npmAssets, $packageNpm);
            }
        }

        $npmAssets = array_merge($npmAssets, $rootNpmAssets);
        ksort($npmAssets, SORT_STRING | SORT_FLAG_CASE);

        return $npmAssets;
    }

    private static function getPackageJsonContent(string $filePath, bool $associative = true): array|\stdClass
    {
        try {
            $packageJsonContent = file_get_contents($filePath);
        } catch (\Exception $exception) {
            throw new \Exception(sprintf('Can not read %s file, ' .
                'make sure the user has permission to read it', $filePath), 0, $exception);
        }
        try {
            $packageJson = json_decode($packageJsonContent, $associative, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            throw new \Exception(sprintf('Can not parse %s" file, ' .
                'make sure it has valid JSON structure', $filePath), 0, $exception);
        }

        return $packageJson;
    }

    /**
     * Runs "pnpm install", updates pnpm-lock.yaml, installs assets to "node_modules/"
     */
    private static function pnpmInstall(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false
    ): void {
        $logLevel = $verbose ? 'info' : 'error';
        $pnpmInstallCmd = ['pnpm', 'install', '--no-frozen-lockfile', '--loglevel', $logLevel];

        if (self::runProcess($inputOutput, $pnpmInstallCmd, $timeout) !== 0) {
            throw new \RuntimeException('Failed to generate pnpm-lock.yaml');
        }
    }

    private static function runProcess(IOInterface $inputOutput, array $cmd, int $timeout): int
    {
        $inputOutput->write(implode(' ', $cmd));

        $pnpmInstall = new Process($cmd, null, null, null, $timeout);
        $pnpmInstall->run(function ($outputType, string $data) use ($inputOutput) {
            if ($outputType === Process::OUT) {
                $inputOutput->write($data, false);
            } else {
                $inputOutput->writeError($data, false);
            }
        });

        return $pnpmInstall->getExitCode();
    }

    /**
     * Runs "pnpm install", installs assets to "node_modules/" using only pnpm-lock.yaml
     */
    private static function pnpmCi(IOInterface $inputOutput, int $timeout = 60, bool $verbose = false): void
    {
        $logLevel = $verbose ? 'info' : 'error';
        $npmCiCmd = ['pnpm', 'install', '--lockfile-only', '--loglevel', $logLevel];

        if (self::runProcess($inputOutput, $npmCiCmd, $timeout) !== 0) {
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
