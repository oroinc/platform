<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares extended entity configs for processing by other commands.
 */
class CacheCheckCommand extends Command
{
    protected static $defaultName = 'oro:entity-extend:cache:check';

    private ExtendConfigDumper $extendConfigDumper;

    public function __construct(ExtendConfigDumper $extendConfigDumper)
    {
        $this->extendConfigDumper = $extendConfigDumper;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Cache directory')
            ->setHidden(true)
            ->setDescription('Prepares extended entity configs for processing by other commands.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command makes sure that extended entity configs
are ready to be processed by other commands.

  <info>php %command.full_name%</info>

<error>This is an internal command. Please do not run it manually.</error>

The <info>--cache-dir</info> option can be used to dump the extended entity config cache
to a different location and check it there.

  <info>php %command.full_name% --cache-dir=<path></info>

HELP
            )
            ->addUsage('--cache-dir=<path>')
        ;
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Check extended entity configs');

        $cacheDir = $input->getOption('cache-dir');
        $originalCacheDir = $this->extendConfigDumper->getCacheDir();

        if (empty($cacheDir) || $cacheDir === $originalCacheDir) {
            $this->extendConfigDumper->checkConfig();
        } else {
            $this->extendConfigDumper->setCacheDir($cacheDir);
            try {
                $this->extendConfigDumper->checkConfig();
            } catch (\Exception $e) {
                $this->extendConfigDumper->setCacheDir($originalCacheDir);
                throw $e;
            }
        }

        return 0;
    }
}
