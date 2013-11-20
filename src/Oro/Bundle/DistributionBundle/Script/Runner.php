<?php

namespace Oro\Bundle\DistributionBundle\Script;


use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
     * @param $path
     * @return string
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    protected function run($path)
    {
        if (file_exists($path)) {
            $process = (new ProcessBuilder())
                ->setPrefix('/usr/bin/php')
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
        return  $this->installationManager->getInstallPath($package) . '/' . $scriptFileName;
    }
}
