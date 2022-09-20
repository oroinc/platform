<?php
declare(strict_types=1);

namespace Oro\Bundle\AssetBundle\Command;

use Oro\Bundle\InstallerBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs and builds application assets.
 */
class OroAssetsInstallCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'oro:assets:install';

    protected function configure()
    {
        $this
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlink instead of copying')
            ->addOption(
                'relative-symlink',
                null,
                InputOption::VALUE_NONE,
                'Symlink using relative path instead of absolute'
            )
            ->setDescription('Installs and builds application assets.')
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command installs and builds assets.

  <info>php %command.full_name%</info>

If the <info>--symlink</info> option is provided this command will create symlinks instead
of copying the files (it may be especially useful during development):

  <info>php %command.full_name% --symlink</info>
  
If the <info>--relative-symlink</info> option is provided, the command creates symlinks using relative paths instead
of absolute:

  <info>php %command.full_name% --relative-symlink</info>

You may run individual steps if necessary as follows:

  <info>php {$_SERVER['PHP_SELF']} oro:localization:dump</info>
  <info>php {$_SERVER['PHP_SELF']} assets:install [--symlink][--relative-symlink]</info>
  <info>php {$_SERVER['PHP_SELF']} oro:assets:build --npm-install</info>

HELP
            )
            ->addUsage('--symlink')
        ;

        parent::configure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->getParameter('kernel.environment') === 'test') {
            $output->writeln('Assets build skipped. Assets are not needed for test environment.');
            return self::SUCCESS;
        }
        $assetsOptions = [];
        if ($input->hasOption('symlink') && $input->getOption('symlink')) {
            $assetsOptions['--symlink'] = true;
        }

        if ($input->hasOption('relative-symlink') && $input->getOption('relative-symlink')) {
            $assetsOptions['--symlink'] = true;
            $assetsOptions['--relative'] = true;
        }

        $commandExecutor = $this->getCommandExecutor($input, $output);
        $commandExecutor
            ->runCommand('oro:localization:dump')
            ->runCommand('assets:install', $assetsOptions)
            ->runCommand('oro:assets:build');

        return 0;
    }
}
