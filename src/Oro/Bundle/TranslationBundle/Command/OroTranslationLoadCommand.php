<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This command loads translations to database
 * It performs many queries to DB. To optimize it - was used native SQL queries and batch inserts
 */
final class OroTranslationLoadCommand extends ContainerAwareCommand
{
    const BATCH_INSERT_ROWS_COUNT = 50;

    /** @var string */
    protected static $defaultName = 'oro:translation:load';

    /** @var ManagerRegistry */
    private $registry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var DatabasePersister */
    private $databasePersister;

    /** @var LanguageProvider */
    private $languageProvider;

    /**
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     * @param DatabasePersister $databasePersister
     * @param LanguageProvider $languageProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        DatabasePersister $databasePersister,
        LanguageProvider $languageProvider
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->databasePersister = $databasePersister;
        $this->languageProvider = $languageProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Loads translations into DB')
            ->addOption(
                'languages',
                'l',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Languages to load.'
            )
            ->addOption('rebuild-cache', null, InputOption::VALUE_NONE, 'Rebuild translation cache');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableLocales = array_keys($this->languageProvider->getAvailableLanguages());

        $locales = $input->getOption('languages') ?: $availableLocales;

        $output->writeln(
            sprintf(
                '<info>Available locales</info>: %s. <info>Should be processed:</info> %s.',
                implode(', ', $availableLocales),
                implode(', ', $locales)
            )
        );

        $databaseTranslationLoader = $this->getContainer()->get('oro_translation.database_translation.loader');
        $databaseTranslationLoader->setDisabled();

        if ($input->getOption('rebuild-cache')) {
            $this->translator->rebuildCache();
        }

        $em = $this->getEntityManager(Translation::class);
        $em->beginTransaction();
        try {
            $this->processLocales($locales, $output);
            $em->commit();
            $output->writeln(sprintf('<info>All messages successfully processed.</info>'));
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        $databaseTranslationLoader->setEnabled();

        if ($input->getOption('rebuild-cache')) {
            $output->write(sprintf('<info>Rebuilding cache ... </info>'));
            $this->translator->rebuildCache();
        }

        $output->writeln(sprintf('<info>Done.</info>'));
    }

    /**
     * @param array $locales
     * @param OutputInterface $output
     * @return array
     */
    private function processLocales(array $locales, OutputInterface $output)
    {
        $languageRepository = $this->getEntityManager(Language::class)->getRepository(Language::class);
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

    /**
     * @param string $class
     *
     * @return EntityManager
     */
    private function getEntityManager($class)
    {
        return $this->registry->getManagerForClass($class);
    }
}
