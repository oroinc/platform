<?php

namespace Oro\Bundle\DistributionBundle\Script;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Helps to manage third party packages, by executing install, update and uninstall scripts.
 * Also it can execute commands to clean or warmup cache ot to update Oro application.
 */
class Runner
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    protected $installationManager;

    /**
     * @var string|null
     */
    protected $applicationProjectDir;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var int
     */
    public $timeout = 600;

    /**
     * @param InstallationManager $installationManager
     * @param LoggerInterface     $logger
     * @param string              $applicationProjectDir
     * @param string              $environment
     */
    public function __construct(
        InstallationManager $installationManager,
        LoggerInterface $logger,
        $applicationProjectDir,
        $environment
    ) {
        $this->installationManager = $installationManager;
        $this->logger = $logger;
        $this->applicationProjectDir = realpath($applicationProjectDir);
        $this->environment = $environment;
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function runInstallScripts(PackageInterface $package)
    {
        return $this->run($this->getPackageScriptPath($package, 'install.php'));
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function runUninstallScripts(PackageInterface $package)
    {
        return $this->run($this->getPackageScriptPath($package, 'uninstall.php'));
    }

    /**
     * @param PackageInterface $updatedPackage
     * @param string $previousPackageVersion
     * @return string
     */
    public function runUpdateScripts(PackageInterface $updatedPackage, $previousPackageVersion)
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
        return $this->runCommand(
            sprintf(
                'oro:platform:update --force --timeout=%s',
                $this->timeout
            )
        );
    }

    /**
     * @return string
     * @throws ProcessFailedException
     */
    public function clearApplicationCache()
    {
        return $this->runCommand('cache:clear --no-debug');
    }

    /**
     * @return string
     * @throws ProcessFailedException
     */
    public function removeApplicationCache()
    {
        return $this->runCommand('cache:clear --no-warmup');
    }

    /**
     * Removes dependency container an bundles definitions from the main application cache.
     * Needed to be executed after package has been uninstalled so that main application (bin/console) could be built
     */
    public function removeCachedFiles()
    {
        if (!$this->applicationProjectDir) {
            return;
        }
        $finder = new Finder();
        $finder->files()
            ->in($this->applicationProjectDir)
            ->name('bundles.php')
            ->name('*ProjectContainer.php');

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            if (is_file($file->getPathname())) {
                $this->logger->info(sprintf('Removing %s', $file->getPathname()));
                unlink($file->getPathname());
            }
        }
    }

    /**
     * @param PackageInterface[] $packages
     *
     * @return string
     */
    public function loadDemoData(array $packages)
    {
        return $this->executeBatchCommand($packages, 'oro:package:demo:load');
    }

    /**
     * @return string
     */
    public function clearDistApplicationCache()
    {
        return $this->runCommand('cache:clear --no-warmup', 'dist');
    }

    /**
     * @param string $path
     * @return string
     * @throws ProcessFailedException
     */
    protected function run($path)
    {
        if (file_exists($path)) {
            $command = sprintf('oro:platform:run-script "%s"', $path);

            return $this->runCommand($command);
        } else {
            $this->logger->info(sprintf('There is no %s file', $path));
        }

        return null;
    }

    /**
     * @param string $command - e.g. clear:cache --no-warmup
     * @param string $application - console or dist
     *
     * @return string
     * @throws ProcessFailedException
     */
    protected function runCommand($command, $application = 'console')
    {
        $phpPath = $this->getPhpExecutablePath();

        $command = sprintf(
            '"%s" "%s/%s" %s --env=%s',
            $phpPath,
            $this->applicationProjectDir . '/bin',
            $application,
            $command,
            $this->environment
        );

        $this->logger->info(sprintf('Executing "%s"', $command));

        $process = new Process($command);
        $process->setWorkingDirectory(realpath($this->applicationProjectDir)); // project root
        $process->setTimeout($this->timeout);

        $process->run();

        if (!$process->isSuccessful()) {
            $processFailedException = new ProcessFailedException($process);
            $this->logger->error($processFailedException->getMessage());
            throw $processFailedException;
        }

        $output = $process->getOutput();
        $this->logger->info($output);

        return $output;
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

    /**
     * @param PackageInterface[] $packages
     * @param string $command
     *
     * @return string
     */
    protected function executeBatchCommand(array $packages, $command)
    {
        $paths = [];
        foreach ($packages as $package) {
            $paths[] = $this->installationManager->getInstallPath($package);
        }

        $commands = $this->makeCommands($paths, $command);
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
                $commands[$commandIndex] .= ' ' . $path;
            } else {
                $commands[++$commandIndex] = $commandPrefix . ' ' . $path;
            }
        }

        return $commands;
    }
}
