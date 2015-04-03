<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OroTranslationUpdateCustomCommand extends ContainerAwareCommand
{
    const BATCH_SIZE = 1000;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:update-custom')
            ->setDescription('Update user defined translations');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $this->getContainer()->getParameter('kernel.default_locale');
        $container = $this->getContainer();
        $oroEntityManager = $container->get('doctrine.orm.entity_manager');
        $translationRepository = $oroEntityManager->getRepository(Translation::ENTITY_NAME);
        /**
         * disable database loader to not get translations from database
         */
        $container->set(
            'oro_translation.database_translation.loader',
            new EmptyArrayLoader()
        );

        $translations = $container->get('translator.default')->getTranslations();
        $customTranslations = $translationRepository->findBy(['locale' => $locale]);
        $updated = 0;

        foreach ($customTranslations as $customTranslation) {
            if (isset (
                $translations[$customTranslation->getDomain()][$customTranslation->getKey()])
            ) {
                $customTranslation->setValue(
                    $translations[$customTranslation->getDomain()][$customTranslation->getKey()]
                );
                $updated++;
                if (($updated % self::BATCH_SIZE) === 0) {
                    $oroEntityManager->flush();
                    $oroEntityManager->clear();
                }
            }
        }

        $oroEntityManager->flush();
        $oroEntityManager->clear();

        $output->writeln('<info>Updated ' . $updated . ' values</info>');
    }
}
