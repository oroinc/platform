<?php

namespace Oro\Bundle\InstallerBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @todo temporary workaround. will be removed in BAP-16194
 */
class AssetsInstallCommandListener
{
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $kernelProjectDir;

    /**
     * @param Filesystem $filesystem
     * @param string     $kernelProjectDir
     */
    public function __construct(Filesystem $filesystem, $kernelProjectDir)
    {
        $this->filesystem = $filesystem;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function beforeExecute(ConsoleCommandEvent $event)
    {
        if ($this->isAssetsInstallCommand($event->getCommand())) {
            $this->moveAssets(
                $event->getOutput(),
                $this->getAssetsDir($event->getInput()),
                $this->getTempDir($event->getInput())
            );
        }
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function afterExecute(ConsoleTerminateEvent $event)
    {
        if ($this->isAssetsInstallCommand($event->getCommand())) {
            $this->moveAssets(
                $event->getOutput(),
                $this->getTempDir($event->getInput()),
                $this->getAssetsDir($event->getInput())
            );
        }
    }

    /**
     * @param Command $command
     *
     * @return bool
     */
    private function isAssetsInstallCommand(Command $command)
    {
        return $command instanceof AssetsInstallCommand;
    }

    /**
     * @param OutputInterface $output
     * @param string          $srcDir
     * @param string          $targetDir
     */
    private function moveAssets(OutputInterface $output, $srcDir, $targetDir)
    {
        foreach ($this->getAssets() as $assetDirName) {
            $fromDir = $srcDir . $assetDirName;
            $toDir = $targetDir . $assetDirName;
            $output->writeln(sprintf('Move "%s" to "%s"', $fromDir, $toDir));
            if ($this->filesystem->exists($fromDir)) {
                $this->filesystem->remove($toDir);
                $this->filesystem->rename($fromDir, $toDir);
            } else {
                $output->writeln(sprintf('WARNING: The directory "%s" does not exist', $fromDir));
            }
        }
    }

    /**
     * @return array
     */
    private function getAssets()
    {
        return ['bowerassets', 'npmassets', 'components'];
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getAssetsDir(InputInterface $input)
    {
        return $this->getPublicDir($input) . '/bundles/';
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getTempDir(InputInterface $input)
    {
        return $this->getPublicDir($input) . '/js/';
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getPublicDir(InputInterface $input)
    {
        $dir = rtrim($input->getArgument('target'), '/');

        if ('public' === $dir) {
            $dir = $this->kernelProjectDir . DIRECTORY_SEPARATOR . $dir;
        }

        return $dir;
    }
}
