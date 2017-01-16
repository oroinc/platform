<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OroTranslationLoadCommand extends ContainerAwareCommand
{
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
            );
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

        /* @var $translationManager TranslationManager */
        $translationManager = $this->getContainer()->get('oro_translation.manager.translation');

        $translator = $this->getTranslator();
///        $translator->rebuildCache();
        $start = time();
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(TranslationKey::class);
        $repo = $em->getRepository(TranslationKey::class);
        $connection = $em->getConnection();
        $translationKeysData = $repo->createQueryBuilder('tk')
            ->select('tk.id, tk.domain', 'tk.key')
            ->getQuery()
            ->getArrayResult();
        $translationKeys = [];
        foreach ($translationKeysData as $item) {
            $translationKeys[$item['domain']][$item['key']] = $item['id'];
        }
        foreach ($locales as $locale) {
            $language = $em->getRepository(Language::class)->findOneBy(['code' => $locale]);
            $domains = $translator->getCatalogue($locale)->all();

            $output->writeln(sprintf('<info>Loading translations [%s] (%d) ...</info>', $locale, count($domains)));

            foreach ($domains as $domain => $messages) {
                $output->write(sprintf('  > loading [%s] (%d) ... ', $domain, count($messages)));

                foreach ($messages as $key => $value) {
                    if (!isset($translationKeys[$domain][$key])) {
                        $connection->insert('oro_translation_key', [
                            'domain' => $domain,
                            'key' => $key,
                        ]);
                        $translationKeys[$domain][$key] =$connection->lastInsertId(
                            $connection->getDatabasePlatform() instanceof PostgreSqlPlatform
                                ? 'oro_translation_key_id_seq'
                                : null
                        );
                    }
                    if (!$connection->update('oro_translation',
                        ['value' => $value],
                        [
                            'translation_key_id' => $translationKeys[$domain][$key],
                            'language_id' => $language->getId()
                        ]
                    )) {
                        $connection->insert(
                            'oro_translation',
                            [
                                'translation_key_id' => $translationKeys[$domain][$key],
                                'language_id' => $language->getId(),
                                'value' => $value,
                                'scope' => Translation::SCOPE_SYSTEM,
                            ]
                        );
                    }
                    //$translationManager->saveTranslation($key, $value, $locale, $domain);
                }

                $output->writeln(sprintf('processed %d records.', count($messages)));
            }
        }

        $output->writeln(sprintf('<info>All messages successfully loaded.</info>'));

        $output->write(sprintf('<info>Rebuilding cache ... </info>'));

        // restore DB loader
        $this->getContainer()->set('oro_translation.database_translation.loader', $translationLoader);

        //$translator->rebuildCache();

        $output->writeln(sprintf('<info>Done.</info>'));
        echo time() - $start;
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }
}
