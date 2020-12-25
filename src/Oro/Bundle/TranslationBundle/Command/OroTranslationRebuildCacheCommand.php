<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to rebuild the translation cache.
 */
class OroTranslationRebuildCacheCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:rebuild-cache';

    /** @var RebuildTranslationCacheProcessor */
    private $rebuildTranslationCacheProcessor;

    /**
     * @param RebuildTranslationCacheProcessor $rebuildTranslationCacheProcessor
     */
    public function __construct(RebuildTranslationCacheProcessor $rebuildTranslationCacheProcessor)
    {
        parent::__construct();
        $this->rebuildTranslationCacheProcessor = $rebuildTranslationCacheProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function configure()
    {
        $this->setDescription('Rebuilds the translation cache.');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->text('Rebuilding the translation cache ...');

        if (!$this->rebuildTranslationCacheProcessor->rebuildCache()) {
            $io->error('The rebuild failed.');

            return 1;
        }

        $io->success('The rebuild complete.');

        return 0;
    }
}
