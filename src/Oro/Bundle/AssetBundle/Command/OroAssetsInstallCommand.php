<?php

namespace Oro\Bundle\AssetBundle\Command;

use Oro\Bundle\InstallerBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command installs application assets
 */
class OroAssetsInstallCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'oro:assets:install';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install and build assets, js routes, dump js translation etc.')
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $assetsOptions = [];
        if ($input->hasOption('symlink') && $input->getOption('symlink')) {
            $assetsOptions['--symlink'] = true;
        }

        $commandExecutor = $this->getCommandExecutor($input, $output);
        $commandExecutor
            ->runCommand('fos:js-routing:dump', ['--process-isolation' => true])
            ->runCommand('oro:localization:dump')
            ->runCommand('assets:install', $assetsOptions)
            ->runCommand('oro:assets:build', ['--npm-install'=> true]);

        return 0;
    }
}
