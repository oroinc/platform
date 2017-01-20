<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * This command loads translations to database
 * It performs many queries to DB. To optimize it - was used native SQL queries and batch inserts
 */
final class OroTranslationLoadCommand extends ContainerAwareCommand
{
    const BATCH_INSERT_ROWS_COUNT = 50;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:load')
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
        /** @var $languageProvider LanguageProvider */
        $languageProvider = $this->getContainer()->get('oro_translation.provider.language');
        $availableLocales = array_keys($languageProvider->getAvailableLanguages());

        $locales = $input->getOption('languages') ?: $availableLocales;

        $output->writeln(
            sprintf(
                '<info>Available locales</info>: %s. <info>Should be processed:</info> %s.',
                implode(', ', $availableLocales),
                implode(', ', $locales)
            )
        );

        // backup DB loader
        $translationLoader = $this->getContainer()->get('oro_translation.database_translation.loader');

        // disable DB loader to exclude existing translations from database
        $this->getContainer()->set('oro_translation.database_translation.loader', new EmptyArrayLoader());

        if ($input->getOption('rebuild-cache')) {
            $this->getTranslator()->rebuildCache();
        }

        $connection = $this->getConnection(Translation::class);
        $connection->beginTransaction();
        try {
            $this->processLocales($locales, $output);
            $connection->commit();
            $output->writeln(sprintf('<info>All messages successfully loaded.</info>'));
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        // restore DB loader
        $this->getContainer()->set('oro_translation.database_translation.loader', $translationLoader);

        if ($input->getOption('rebuild-cache')) {
            $output->write(sprintf('<info>Rebuilding cache ... </info>'));
            $this->getTranslator()->rebuildCache();
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
        $languageRepository = $this->getEntityRepository(Language::class);
        /** @var TranslationRepository $translationRepository */
        $translationRepository = $this->getEntityRepository(Translation::class);
        $connection = $this->getConnection(Translation::class);

        foreach ($locales as $locale) {
            $language = $languageRepository->findOneBy(['code' => $locale]);
            if (!$language) {
                $output->writeln(sprintf('<info>Language "%s" not found</info>', $locale));
                continue;
            }
            $domains = $this->getTranslator()->getCatalogue($locale)->all();

            $output->writeln(sprintf('<info>Loading translations [%s] (%d) ...</info>', $locale, count($domains)));

            $translationKeys = $this->processTranslationKeys($domains);
            $translations = $translationRepository->getTranslationsData($language->getId());
            $sqlData = [];
            foreach ($domains as $domain => $messages) {
                $output->write(sprintf('  > loading [%s] (%d) ... ', $domain, count($messages)));
                foreach ($messages as $key => $value) {
                    if (isset($translations[$translationKeys[$domain][$key]])) {
                        $this->updateTranslation(
                            $value,
                            $language->getId(),
                            $translations[$translationKeys[$domain][$key]]
                        );
                    } else {
                        $sqlData[] = sprintf(
                            '(%d, %d, %s, %s)',
                            $translationKeys[$domain][$key],
                            $language->getId(),
                            $connection->quote($value),
                            Translation::SCOPE_SYSTEM
                        );

                        if (self::BATCH_INSERT_ROWS_COUNT === count($sqlData)) {
                            $this->executeBatchTranslationInsert($sqlData);
                            $sqlData = [];
                        }
                    }
                }
                $output->writeln(sprintf('processed %d records.', count($messages)));
            }
            $this->executeBatchTranslationInsert($sqlData);
        }
    }

    /**
     * Loads translation keys to DB if needed
     *
     * @param array $domains
     *
     * @return array
     */
    private function processTranslationKeys(array $domains)
    {
        $connection = $this->getConnection(TranslationKey::class);
        /** @var TranslationKeyRepository $translationKeyRepository */
        $translationKeyRepository = $this->getEntityRepository(TranslationKey::class);

        $translationKeys = $translationKeyRepository->getTranslationKeysData();
        $sql = sprintf(
            'INSERT INTO oro_translation_key (%s, %s) VALUES ',
            $connection->quoteIdentifier('domain'),
            $connection->quoteIdentifier('key')
        );
        $sqlData = [];
        $needUpdate = false;
        foreach ($domains as $domain => $messages) {
            foreach ($messages as $key => $value) {
                if (!isset($translationKeys[$domain][$key])) {
                    $sqlData[] = sprintf('(%s, %s)', $connection->quote($domain), $connection->quote($key));
                    $translationKeys[$domain][$key] = 1;
                    $needUpdate = true;

                    if (self::BATCH_INSERT_ROWS_COUNT === count($sqlData)) {
                        $connection->executeQuery($sql . implode(', ', $sqlData));
                        $sqlData = [];
                    }
                }
            }
        }
        if (0 !== count($sqlData)) {
            $connection->executeQuery($sql . implode(', ', $sqlData));
        }
        if ($needUpdate) {
            $translationKeys = $translationKeyRepository->getTranslationKeysData();
        }

        return $translationKeys;
    }

    /**
     * @param array $sqlData

     * @return array
     */
    private function executeBatchTranslationInsert(array $sqlData)
    {
        $sql = 'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES ';
        if (0 !== count($sqlData)) {
            $this->getConnection(Translation::class)->executeQuery($sql . implode(', ', $sqlData));
        }
    }

    /**
     * Update translation record in DB only if record is changed and scope in DB for this record is System
     *
     * @param string $value
     * @param int $languageId
     * @param array $translationDataItem
     *
     * @return array
     */
    private function updateTranslation($value, $languageId, array $translationDataItem)
    {
        if ($translationDataItem['scope'] === Translation::SCOPE_SYSTEM && $translationDataItem['value'] !== $value) {
            $this->getConnection(Translation::class)->update(
                'oro_translation',
                ['value' => $value],
                [
                    'translation_key_id' => $translationDataItem['translation_key_id'],
                    'language_id' => $languageId
                ]
            );
        }
    }

    /**
     * @param string $class
     *
     * @return Connection
     */
    private function getConnection($class)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($class);

        return $em->getConnection();
    }

    /**
     * @param string $class
     *
     * @return EntityRepository
     */
    private function getEntityRepository($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @return Translator
     */
    private function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }
}
