<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\DataCollectorTranslator;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

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
        /* @var $translator DataCollectorTranslator */
        $translator = $this->getContainer()->get('translator');

        if (null === ($locales = $input->getOption('languages'))) {
            $locales = array_unique(array_merge($translator->getFallbackLocales(), [$translator->getLocale()]));
        }

        /* @var $translationManager TranslationManager */
        $translationManager = $this->getContainer()->get('oro_translation.manager.translation');

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

        $translationManager->invalidateCache();

        $output->writeln(sprintf('<info>All messages successfully loaded.</info>'));
    }
}
