<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads translations to the database.
 */
final class OroTranslationLoadCommand extends Command
{
    public const BATCH_INSERT_ROWS_COUNT = 50;

    /** @var string */
    protected static $defaultName = 'oro:translation:load';

    private ManagerRegistry $registry;
    private TranslatorInterface $translator;
    private DatabasePersister $databasePersister;
    private LanguageProvider $languageProvider;
    private OrmTranslationLoader $databaseTranslationLoader;

    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        DatabasePersister $databasePersister,
        LanguageProvider $languageProvider,
        OrmTranslationLoader $databaseTranslationLoader
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->databasePersister = $databasePersister;
        $this->languageProvider = $languageProvider;
        $this->databaseTranslationLoader = $databaseTranslationLoader;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'languages',
                'l',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Languages to load'
            )
            ->addOption('rebuild-cache', null, InputOption::VALUE_NONE, 'Rebuild translation cache')
            ->setDescription('Loads translations to the database.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command loads translations to the database.

  <info>php %command.full_name%</info>

The <info>--languages</info> option can be used to limit the list of the loaded languages:

  <info>php %command.full_name% --languages=<language1> --languages=<language2> --languages=<languageN></info>

The <info>--rebuild-cache</info> option will trigger the translation cache rebuild after the translations are loaded:

  <info>php %command.full_name% --rebuild-cache</info>

HELP
            )
            ->addUsage('--languages=<language1> --languages=<language2> --languages=<languageN>')
            ->addUsage('--rebuild-cache')
        ;
    }

    /**
     * @throws \Exception
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableLocales = $this->languageProvider->getAvailableLanguageCodes();

        $locales = $input->getOption('languages') ?: $availableLocales;

        $output->writeln(
            sprintf(
                '<info>Available locales</info>: %s. <info>Should be processed:</info> %s.',
                implode(', ', $availableLocales),
                implode(', ', $locales)
            )
        );

        $this->databaseTranslationLoader->setDisabled();

        if ($input->getOption('rebuild-cache')) {
            $this->translator->rebuildCache();
        }

        $em = $this->registry->getManagerForClass(Translation::class);
        $em->beginTransaction();
        try {
            $this->processLocales($locales, $output);
            $em->commit();
            $output->writeln(sprintf('<info>All messages successfully processed.</info>'));
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        $this->databaseTranslationLoader->setEnabled();

        if ($input->getOption('rebuild-cache')) {
            $output->write(sprintf('<info>Rebuilding cache ... </info>'));
            $this->translator->rebuildCache();
        }

        $output->writeln(sprintf('<info>Done.</info>'));
    }

    private function processLocales(array $locales, OutputInterface $output): void
    {
        $languageRepository = $this->registry->getRepository(Language::class);
        foreach ($locales as $locale) {
            if (!$languageRepository->findOneBy(['code' => $locale])) {
                $output->writeln(sprintf('<info>Language "%s" not found</info>', $locale));
                continue;
            }
            $catalogData = $this->translator->getCatalogue($locale)->all();
            $output->writeln(sprintf('<info>Loading translations [%s] (%d) ...</info>', $locale, count($catalogData)));
            $this->databasePersister->persist($locale, $catalogData, Translation::SCOPE_SYSTEM);

            foreach ($catalogData as $domain => $messages) {
                $output->writeln(sprintf('  > loading [%s] ... processed %d records.', $domain, count($messages)));
            }
        }
    }
}
