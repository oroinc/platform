<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OroTranslationUpdateCustomCommand extends ContainerAwareCommand
{
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
        $oroEntityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $translationRepository = $oroEntityManager->getRepository(Translation::ENTITY_NAME);
        $translator = $this->getContainer()->get('translator');

        $translations = $translator->getTranslations([], $locale);
        $customTranslations = $translationRepository->findAll();
        $updated = 0;
        foreach ($customTranslations as $customTranslation) {
            if (isset (
                $translations[$customTranslation->getDomain()][$customTranslation->getKey()])
            ) {
                $customTranslation->setValue(
                    $translations[$customTranslation->getDomain()][$customTranslation->getKey()]
                );
                $oroEntityManager->persist($customTranslation);
                $updated++;
            }
        }
        $oroEntityManager->flush();

        $output->writeln('<info>updated ' . $updated . ' values</info>');
    }
}
