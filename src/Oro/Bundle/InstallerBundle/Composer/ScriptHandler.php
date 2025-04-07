<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Exception;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Script handler for composer.
 * - installs npm assets
 * - sets permission on app directories
 */
class ScriptHandler
{
    /**
     * Installs npm assets
     *
     * @param Event $event A instance
     * @throws Exception
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
            try {
                $packageJsonContent = file_get_contents('package.json');
            } catch (Exception $exception) {
                throw new Exception('Can not read "package.json" file, ' .
                    'make sure the user has permission to read it', 0, $exception);
            }
            try {
                $packageJson = json_decode($packageJsonContent, false, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $exception) {
                throw new Exception('Can not parse "package.json" file, ' .
                    'make sure it has valid JSON structure', 0, $exception);
            }
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
            ->dumpFile('package.json', json_encode($packageJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . "\n");

        $isVerbose = $event->getIO()->isVerbose();
        if (!$filesystem->exists('package-lock.json')) {
            // Creates lock file, installs assets.
            self::npmInstall($event->getIO(), $options['process-timeout'], $isVerbose);
        } else {
            // Installs assets using lock file.
            self::npmCi($event->getIO(), $options['process-timeout'], $isVerbose);
        }
    }

    /**
     * Updates npm assets
     *
     * @param Event $event A instance
     */
    public static function updateAssets(Event $event): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove('package-lock.json');

        self::installAssets($event);
    }

    /**
     * Collects npm assets from extra.npm section of installed packages.
     *
     * @throws Exception
     */
    private static function collectNpmAssets(Composer $composer): array
    {
        $rootPackage = $composer->getPackage();

        // Gets array of installed packages.
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();

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
                    throw new Exception('Where are some conflicting npm packages "' .
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

    /**
     * Runs "npm install", updates package-lock.json, installs assets to "node_modules/"
     */
    private static function npmInstall(
        IOInterface $inputOutput,
        int $timeout = 60,
        bool $verbose = false
    ): void {
        $logLevel = $verbose ? 'info' : 'error';
        $npmInstallCmd = ['npm', 'install', '--no-audit', '--save-exact', '--loglevel', $logLevel];

        if (self::runProcess($inputOutput, $npmInstallCmd, $timeout) !== 0) {
            throw new RuntimeException('Failed to generate package-lock.json');
        }
    }

    private static function runProcess(IOInterface $inputOutput, array $cmd, int $timeout): int
    {
        $inputOutput->write(implode(' ', $cmd));

        $npmInstall = new Process($cmd, null, null, null, $timeout);
        $npmInstall->run(function ($outputType, string $data) use ($inputOutput) {
            if ($outputType === Process::OUT) {
                $inputOutput->write($data, false);
            } else {
                $inputOutput->writeError($data, false);
            }
        });

        return $npmInstall->getExitCode();
    }

    /**
     * Runs "npm ci", installs assets to "node_modules/" using only package-lock.json
     */
    private static function npmCi(IOInterface $inputOutput, int $timeout = 60, bool $verbose = false): void
    {
        $logLevel = $verbose ? 'info' : 'error';
        $npmCiCmd = ['npm', 'ci', '--loglevel', $logLevel];

        if (self::runProcess($inputOutput, $npmCiCmd, $timeout) !== 0) {
            throw new RuntimeException('Failed to install npm assets');
        }
    }

    /**
     * Set permissions for directories
     */
    public static function setPermissions(Event $event)
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
}
