<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheCheckCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:cache:check')
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
        $dumper   = $this->getContainer()->get('oro_entity_extend.tools.dumper');

        $originalCacheDir = $dumper->getCacheDir();
        if (empty($cacheDir) || $cacheDir === $originalCacheDir) {
            $dumper->checkConfig();
        } else {
            $dumper->setCacheDir($cacheDir);
            try {
                $dumper->checkConfig();
            } catch (\Exception $e) {
                $dumper->setCacheDir($originalCacheDir);
                throw $e;
            }
        }
    }
}
