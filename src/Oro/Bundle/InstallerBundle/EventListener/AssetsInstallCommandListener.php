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
 * The listener that moves 'components' directory
 * from "public/bundles" directory to a temporary directory before "assets:install" command
 * and restore these directories after this command finished.
 * This is required because this command removes all assets that are not related to bundles.
 */
class AssetsInstallCommandListener
{
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $kernelProjectDir;

    public function __construct(Filesystem $filesystem, string $kernelProjectDir)
    {
        $this->filesystem = $filesystem;
        $this->kernelProjectDir = $kernelProjectDir;
    }

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
        return ['components'];
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
        $defaultPublicDir = 'public';
        $target = $input->getArgument('target') ?? 'public';
        $dir = rtrim($target, '/');

        if (!$dir) {
            $composerFilePath = $this->kernelProjectDir . '/composer.json';
            if (!file_exists($composerFilePath)) {
                $dir = $defaultPublicDir;
            } else {
                $composerConfig = json_decode(file_get_contents($composerFilePath), true);
                if (isset($composerConfig['extra']['public-dir'])) {
                    $dir = $composerConfig['extra']['public-dir'];
                }
            }
        }

        if ($dir === $defaultPublicDir) {
            $dir = $this->kernelProjectDir . DIRECTORY_SEPARATOR . $dir;
        }

        return $dir;
    }
}
