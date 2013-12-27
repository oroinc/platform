<?php

namespace Oro\Bundle\DistributionBundle\Script;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Runner
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    protected $installationManager;

    /**
     * @var string|null
     */
    protected $applicationRootDir;

    /**
     * @param InstallationManager $installationManager
     * @param string $applicationRootDir
     */
    public function __construct(InstallationManager $installationManager, $applicationRootDir)
    {
        $this->installationManager = $installationManager;
        $this->applicationRootDir = realpath($applicationRootDir);
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function install(PackageInterface $package)
    {
        return $this->run($this->getPackageScriptPath($package, 'install.php'));
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function uninstall(PackageInterface $package)
    {
        return $this->run($this->getPackageScriptPath($package, 'uninstall.php'));
    }

    /**
     * @param PackageInterface $updatedPackage
     * @param string $previousPackageVersion
     * @return string
     */
    public function update(PackageInterface $updatedPackage, $previousPackageVersion)
    {
        $migrationScripts = $this->findMigrationScripts($updatedPackage, $previousPackageVersion);
        if (!$migrationScripts) {
            $migrationScripts = [$this->getPackageScriptPath($updatedPackage, 'update.php')];
        }

        $output = [];
        foreach ($migrationScripts as $script) {
            $scriptOutput = $this->run($script);
            if ($scriptOutput) {
                $output[] = $scriptOutput;
            }
        }
        if (!$output) {
            return null;
        }
        return implode(PHP_EOL, $output);
    }

    /**
     * @return string
     * @throws ProcessFailedException
     */
    public function runPlatformUpdate()
    {
        $phpPath = $this->getPhpExecutablePath();
        $command = sprintf('%s app/console oro:platform:update --env=prod', $phpPath);

        return $this->runCommand($command);
    }

    /**
     * Needed to be executed after package has been uninstalled so that main application (app/console) could be built
     */
    public function removeCachedFiles()
    {
        if (!$this->applicationRootDir) {
            return;
        }
        $finder = new Finder();
        $finder->files()
            ->in($this->applicationRootDir)
            ->name('bundles.php')
            ->name('*ProjectContainer.php');

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            if (is_file($file->getPathname())) {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * @param PackageInterface[] $packages
     *
     * @return string
     */
    public function loadFixtures(array $packages)
    {
        $phpPath = $this->getPhpExecutablePath();
        $paths = [];
        foreach ($packages as $package) {
            $paths[] = $this->installationManager->getInstallPath($package);
        }
        $commandPrefix = sprintf('%s app/console oro:package:fixtures:load --env=prod ', $phpPath);
        $commands = $this->makeCommands($paths, $commandPrefix);
        $output = '';
        foreach ($commands as $command) {
            $output .= $this->runCommand($command);
        }

        return $output;

    }

    /**
     * @param array $paths
     * @param string $commandPrefix
     * @param int $commandSize - windows shell-command is limited by 8kb
     *
     * @return array of commands to be executed
     */
    protected function makeCommands(array $paths, $commandPrefix, $commandSize = 8000)
    {
        $commands = [];
        $commandIndex = 0;

        $commands[$commandIndex] = $commandPrefix;
        foreach ($paths as $path) {
            if (strlen($commands[$commandIndex] . $path . ' ') <= $commandSize) {
                $commands[$commandIndex] .= $path . ' ';
            } else {
                $commands[++$commandIndex] = $commandPrefix . $path . ' ';
            }
        }

        return $commands;
    }

    /**
     * @param string $path
     * @return string
     * @throws ProcessFailedException
     */
    protected function run($path)
    {
        if (file_exists($path)) {
            $phpPath = $this->getPhpExecutablePath();
            $command = sprintf('%s app/console oro:platform:run-script --env=prod %s', $phpPath, $path);

            return $this->runCommand($command);
        }
    }

    protected function runCommand($command)
    {
        $process = new Process($command);
        $process->setWorkingDirectory(realpath($this->applicationRootDir . '/..')); // project root
        $process->setTimeout(600);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * @param PackageInterface $package
     * @param string $scriptFileName
     * @return string
     */
    protected function getPackageScriptPath(PackageInterface $package, $scriptFileName)
    {
        return $this->installationManager->getInstallPath($package) . '/' . $scriptFileName;
    }

    /**
     * @param PackageInterface $updatedPackage
     * @param string $previousPackageVersion
     * @return array
     */
    protected function findMigrationScripts(PackageInterface $updatedPackage, $previousPackageVersion)
    {
        $finder = new Finder();
        $iterator = $finder
            ->files()
            ->in($this->installationManager->getInstallPath($updatedPackage))
            ->depth(0)
            ->name('update_*.php');
        $files = [];
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $files[] = $file->getPathname();
        }
        $fetchItemVersion = function ($item) {
            $regexp = '~_([^_]+?)\.php$~i';
            preg_match($regexp, $item, $itemMatches);

            return $itemMatches[1];

        };
        $files = array_filter(
            $files,
            function ($item) use ($previousPackageVersion, $fetchItemVersion) {
                $itemVersion = $fetchItemVersion($item);

                return version_compare($itemVersion, $previousPackageVersion, '>');
            }
        );
        usort(
            $files,
            function ($a, $b) use ($fetchItemVersion) {
                $aVersion = $fetchItemVersion($a);
                $bVersion = $fetchItemVersion($b);

                return version_compare($aVersion, $bVersion);
            }
        );
        return $files;
    }

    /**
     * @return string
     * @throws \RuntimeException when PHP cannot be found
     */
    protected function getPhpExecutablePath()
    {
        if ($path = (new PhpExecutableFinder())->find()) {
            return $path;
        }

        throw new \RuntimeException('PHP cannot be found');
    }
}
