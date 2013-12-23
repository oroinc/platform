<?php

namespace Oro\Bundle\DistributionBundle\Script;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

class Runner
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    protected $installationManager;

    /**
     * @param InstallationManager $installationManager
     */
    public function __construct(InstallationManager $installationManager)
    {
        $this->installationManager = $installationManager;
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
     * @param string $path
     * @return string
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    protected function run($path)
    {
        if (file_exists($path)) {
            $process = (new ProcessBuilder())
                ->setPrefix($this->getPhpExecutablePath())
                ->setArguments([$path])
                ->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return $process->getOutput();
        }
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
        $files = array_filter($files, function ($item) use ($previousPackageVersion, $fetchItemVersion) {
            $itemVersion = $fetchItemVersion($item);

            return version_compare($itemVersion, $previousPackageVersion, '>');
        });
        usort($files, function ($a, $b) use ($fetchItemVersion) {
            $aVersion = $fetchItemVersion($a);
            $bVersion = $fetchItemVersion($b);

            return version_compare($aVersion, $bVersion);
        });
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
