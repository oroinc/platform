<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Makes sure that extended entity configs are ready to be processed by other commands.
 */
class CacheCheckCommand extends Command
{
    protected static $defaultName = 'oro:entity-extend:cache:check';

    /**
     * @var ExtendConfigDumper
     */
    private $extendConfigDumper;

    /**
     * @param ExtendConfigDumper $extendConfigDumper
     */
    public function __construct(ExtendConfigDumper $extendConfigDumper)
    {
        $this->extendConfigDumper = $extendConfigDumper;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription(
                'Makes sure that extended entity configs are ready to be processed by other commands.'
                . ' This is an internal command. Please do not run it manually.'
            )
            ->addOption(
                'cache-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The cache directory'
            );
    }

    /**
     * {@inheritdoc}
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
    }
}
