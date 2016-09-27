<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\DataCollectorTranslator;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OroTranslationLoadCommand extends ContainerAwareCommand
{
    /** @var EntityManagerInterface */
    protected $entityManager;

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
        /* @var $translationManager LanguageProvider */
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

        $resourceCache = new ArrayCache();

        /* @var $translator Translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setResourceCache($resourceCache);

        $translationLoader = $this->getContainer()->get('oro_translation.database_translation.loader');

        // disable database loader to not get translations from database
        $this->getContainer()->set('oro_translation.database_translation.loader', new EmptyArrayLoader());

        /* @var $translationManager TranslationManager */
        $translationManager = $this->getContainer()->get('oro_translation.manager.translation');
        $translationManager->rebuildCache();

        foreach ($locales as $locale) {
            $domains = $translator->getCatalogue($locale)->all();

            $output->writeln(sprintf('<info>Loading translations [%s] (%d) ...</info>', $locale, count($domains)));

            foreach ($domains as $domain => $messages) {
                $records = 0;
                $output->write(sprintf('  > loading [%s] (%d) ... ', $domain, count($messages)));

                foreach ($messages as $key => $value) {
                    if (null !== $translationManager->findValue($key, $locale, $domain)) {
                        continue;
                    }

                    $translationManager->createValue($key, $value, $locale, $domain, true);
                    $records++;
                }

                $translationManager->flush();

                $output->writeln(sprintf('added %d records.', $records));
            }
        }

        // restore DB loader and clear resource loader cache
        $this->getContainer()->set('oro_translation.database_translation.loader', $translationLoader);
        $resourceCache->deleteAll();

        $translationManager->rebuildCache();

        $output->writeln(sprintf('<info>All messages successfully loaded.</info>'));
    }
}
