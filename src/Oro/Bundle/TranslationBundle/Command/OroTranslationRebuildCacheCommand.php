<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds the translation cache.
 */
class OroTranslationRebuildCacheCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:rebuild-cache';

    private RebuildTranslationCacheProcessor $rebuildTranslationCacheProcessor;

    public function __construct(RebuildTranslationCacheProcessor $rebuildTranslationCacheProcessor)
    {
        parent::__construct();
        $this->rebuildTranslationCacheProcessor = $rebuildTranslationCacheProcessor;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->setDescription('Rebuilds the translation cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command rebuilds the translation cache.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
