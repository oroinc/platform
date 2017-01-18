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
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OroTranslationLoadCommand extends ContainerAwareCommand
{
    const BATCH_INSERT_ROWS_COUNT = 50;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:load')
            ->setDescription('Load translations into DB')
            ->addOption(
                'languages',
                'l',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Languages to load.'
            )->addOption('rebuild-cache', 'rc', InputOption::VALUE_OPTIONAL, 'Rebuild translation cache');
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

        // disable DB loader to not get translations from database
        $this->getContainer()->set('oro_translation.database_translation.loader', new EmptyArrayLoader());

        if ($input->getOption('rebuild-cache')) {
            $this->getTranslator()->rebuildCache();
        }

        $start = time();
        $this->processLocales($locales, $output);
        $output->writeln(sprintf('<info>All messages successfully loaded.</info>'));

        // restore DB loader
        $this->getContainer()->set('oro_translation.database_translation.loader', $translationLoader);

        if ($input->getOption('rebuild-cache')) {
            $output->write(sprintf('<info>Rebuilding cache ... </info>'));
            $this->getTranslator()->rebuildCache();
        }

        $output->writeln(sprintf('<info>Done.</info>'));
        echo time() - $start;
    }

    /**
     * @param array $locales
     * @param OutputInterface $output
     * @return array
     */
    protected function processLocales(array $locales, OutputInterface $output)
    {
        $repoLanguage = $this->getEntityRepository(Language::class);
        $connection = $this->getConnection(Translation::class);

        foreach ($locales as $locale) {
            $language = $repoLanguage->findOneBy(['code' => $locale]);
            $domains = $this->getTranslator()->getCatalogue($locale)->all();

            $output->writeln(sprintf('<info>Loading translations [%s] (%d) ...</info>', $locale, count($domains)));

            $translationKeys = $this->processTranslationKeys($domains);
            $translations = $this->getTranslationsData($language->getId());
            $sqlData = [];
            foreach ($domains as $domain => $messages) {
                $output->write(sprintf('  > loading [%s] (%d) ... ', $domain, count($messages)));
                foreach ($messages as $key => $value) {
                    if (isset($translations[$translationKeys[$domain][$key]])) {
                        if ($translations[$translationKeys[$domain][$key]] === Translation::SCOPE_SYSTEM) {
                            $connection->update(
                                'oro_translation',
                                ['value' => $value],
                                [
                                    'translation_key_id' => $translationKeys[$domain][$key],
                                    'language_id' => $language->getId()
                                ]
                            );
                        }
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
     * @param array $domains
     *
     * @return array
     */
    protected function processTranslationKeys(array $domains)
    {
        $connection = $this->getConnection(TranslationKey::class);
        $translationKeys = $this->getTranslationKeysData();
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

                    if (self::BATCH_INSERT_ROWS_COUNT === count($sqlData)) {
                        $connection->executeQuery($sql . implode(', ', $sqlData));
                        $translationKeys[$domain][$key] = 1;
                        $needUpdate = true;
                        $sqlData = [];
                    }
                }
            }
        }
        if (0 !== count($sqlData)) {
            $connection->executeQuery($sql . implode(', ', $sqlData));
        }
        if ($needUpdate) {
            $translationKeys = $this->getTranslationKeysData();
        }

        return $translationKeys;
    }

    /**
     * @return array
     */
    protected function getTranslationKeysData()
    {
        $repository = $this->getEntityRepository(TranslationKey::class);
        $translationKeysData = $repository->createQueryBuilder('tk')
            ->select('tk.id, tk.domain, tk.key')
            ->getQuery()
            ->getArrayResult();
        $translationKeys = [];
        foreach ($translationKeysData as $item) {
            $translationKeys[$item['domain']][$item['key']] = $item['id'];
        }

        return $translationKeys;
    }

    /**
     * @param array $sqlData

     * @return array
     */
    protected function executeBatchTranslationInsert(array $sqlData)
    {
        $sql = 'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES ';
        if (0 !== count($sqlData)) {
            $this->getConnection(Translation::class)->executeQuery($sql . implode(', ', $sqlData));
        }
    }

    /**
     * @param int $languageId
     * @return array
     */
    protected function getTranslationsData($languageId)
    {
        $translationsData = $this->getEntityRepository(Translation::class)
            ->createQueryBuilder('t')
            ->select('IDENTITY(t.translationKey) as translation_key_id, t.scope')
            ->where('t.language = :language')
            ->setParameters(['language' => $languageId])
            ->getQuery()
            ->getArrayResult();
        $translations = [];
        foreach ($translationsData as $item) {
            $translations[$item['translation_key_id']] = $item['scope'];
        }

        return $translations;
    }

    /**
     * @param string $class
     * @return Connection
     */
    protected function getConnection($class)
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
    protected function getEntityRepository($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }
}
