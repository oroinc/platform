<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;

class OroTranslationResetCommand extends ContainerAwareCommand
{
    const BATCH_SIZE = 1000;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:reset')
            ->setDescription('Reset user defined translations')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces operation to be executed.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale                = $this->getContainer()->getParameter('kernel.default_locale');
        $container             = $this->getContainer();
        $translationRepository = $this->getEntityManager()->getRepository(Translation::ENTITY_NAME);
        /**
         * disable database loader to not get translations from database
         */
        $container->set(
            'oro_translation.database_translation.loader',
            new EmptyArrayLoader()
        );

        $translations       = $container->get('translator.default')->getTranslations();
        $customTranslations = $translationRepository->findBy(['locale' => $locale]);
        $force              = $input->getOption('force');

        if (!$force) {
            $output->writeln('<fg=red>ATTENTION</fg=red>: Your custom translations will be reset to default values.');
            $output->writeln('To force execution run command with <info>--force</info> option:');
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));

            $updated = $this->countCustomTranslations($customTranslations, $translations);
            $message = sprintf('    <info>Will be updated %d values</info>', $updated);
        } else {
            $updated = $this->doResetCustomTranslations($customTranslations, $translations);
            $message = sprintf('    <info>Updated %d values</info>', $updated);
        }

        $output->writeln($message);
    }

    /**
     * @param Translation[] $customTranslations
     * @param array         $translations
     * @return int
     */
    protected function doResetCustomTranslations(array $customTranslations, array $translations)
    {
        $updated = 0;
        $em      = $this->getEntityManager();
        foreach ($customTranslations as $customTranslation) {
            if (isset($translations[$customTranslation->getDomain()][$customTranslation->getKey()])) {
                $customTranslation->setValue(
                    $translations[$customTranslation->getDomain()][$customTranslation->getKey()]
                );
                $updated++;
                if (($updated % self::BATCH_SIZE) === 0) {
                    $em->flush();
                    $em->clear();
                }
            }
        }

        $em->flush();
        $em->clear();

        return $updated;
    }

    /**
     * @param Translation[] $customTranslations
     * @param array         $translations
     * @return int
     */
    protected function countCustomTranslations(array $customTranslations, array $translations)
    {
        $updated = 0;
        foreach ($customTranslations as $customTranslation) {
            if (isset($translations[$customTranslation->getDomain()][$customTranslation->getKey()])) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        }

        return $this->entityManager;
    }
}
