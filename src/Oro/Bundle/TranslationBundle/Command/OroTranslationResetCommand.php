<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Reset user defined translations
 */
class OroTranslationResetCommand extends Command
{
    const BATCH_SIZE = 1000;

    /** @var string */
    protected static $defaultName = 'oro:translation:reset';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $kernelDefaultLocale;

    /** @var OrmTranslationLoader */
    private $ormTranslationLoader;

    /**
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param OrmTranslationLoader $ormTranslationLoader
     * @param string $kernelDefaultLocale
     */
    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        OrmTranslationLoader $ormTranslationLoader,
        string $kernelDefaultLocale
    ) {
        $this->kernelDefaultLocale = $kernelDefaultLocale;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->ormTranslationLoader = $ormTranslationLoader;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
        $translationRepository = $this->entityManager->getRepository(Translation::class);

        /**
         * disable database loader to not get translations from database
         */
        $this->ormTranslationLoader->setDisabled();

        $translations       = $this->translator->getTranslations();
        $customTranslations = $translationRepository->findBy(['locale' => $this->kernelDefaultLocale]);
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

        foreach ($customTranslations as $customTranslation) {
            $key = $customTranslation->getTranslationKey();
            if (isset($translations[$key->getDomain()][$key->getKey()])) {
                $customTranslation->setValue(
                    $translations[$key->getDomain()][$key->getKey()]
                );
                $updated++;
                if (($updated % self::BATCH_SIZE) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

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
            $key = $customTranslation->getTranslationKey();
            if (isset($translations[$key->getDomain()][$key->getKey()])) {
                $updated++;
            }
        }

        return $updated;
    }
}
