<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Locales;

/**
 * Replaces default localization parameters during installation.
 *
 * Example:
 *  `oro:install --language=de --formatting-code=de_DE`
 *  will change "Language" value to "de" and "Formatting code" value to "de_DE" for the default localization.
 */
class UpdateLocalizationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:localization:update';

    private ManagerRegistry $doctrine;
    private ?ConfigManager $configManager;

    public function __construct(ManagerRegistry $doctrine, ?ConfigManager $configManager = null)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addOption(
                'formatting-code',
                null,
                InputOption::VALUE_REQUIRED,
                'Formatting code',
                Translator::DEFAULT_LOCALE
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_REQUIRED,
                'Language code',
                Translator::DEFAULT_LOCALE
            )
            ->setHidden(true)
            ->setDescription('Replaces default localization parameters during installation.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command replaces "<comment>en</comment>" in "<comment>Language</comment>" and "<comment>Formatting code</comment>"
options of the default localization with the new values passed as <info>--language</info>
and <info>--formatting-code</info> options to <info>oro:install</info> command. It will also create a new
language entity for the <info>--language</info> option value if such language doesn't exist yet.

  <info>php %command.full_name% --formatting-code=<formatting-code> --language=<language></info>

<error>This is an internal command. Please do not run it manually.</error>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--formatting-code=<formatting-code> --language=<language>')
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languageCode = (string)$input->getOption('language');
        $formattingCode = (string)$input->getOption('formatting-code');

        $localization = $this->findDefaultLocalization();

        if ($localization) {
            $language = $localization->getLanguage();
            if ($language->getCode() !== $languageCode) {
                $existingLanguage = $this->getManager(Language::class)
                    ->getRepository(Language::class)
                    ->findOneBy(['code' => $languageCode]);

                $language = $existingLanguage ?: $this->createLanguage($language, $languageCode);
            }

            $this->updateLocalization($localization, $language, $formattingCode);
        } else {
            throw new \RuntimeException('Default localization not found');
        }

        return Command::SUCCESS;
    }

    private function findDefaultLocalization(): ?Localization
    {
        /** @var LocalizationRepository $localizationRepository */
        $localizationRepository = $this->getManager(Localization::class)
            ->getRepository(Localization::class);

        $defaultLocalizationId = $this->configManager?->get(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION)
        );

        return $defaultLocalizationId
            ? $localizationRepository->find($defaultLocalizationId)
            : $localizationRepository->findOneByLanguageCodeAndFormattingCode(
                Configuration::DEFAULT_LANGUAGE,
                Configuration::DEFAULT_LOCALE
            );
    }

    private function createLanguage(Language $defaultLanguage, string $languageCode): Language
    {
        /** @var EntityManager $em */
        $em = $this->getManager(Language::class);

        $language = new Language();
        $language->setCode($languageCode)
            ->setEnabled(true)
            ->setOrganization($defaultLanguage->getOrganization());

        $em->persist($language);
        $em->flush($language);

        return $language;
    }

    private function updateLocalization(Localization $localization, Language $language, string $formattingCode): void
    {
        $title = Locales::getName($formattingCode, $language->getCode());

        $localization->setFormattingCode($formattingCode)
            ->setLanguage($language)
            ->setName($title)
            ->setDefaultTitle($title);

        $this->getManager(Localization::class)->flush();
    }

    private function getManager(string $className): EntityManager
    {
        return $this->doctrine->getManagerForClass($className);
    }
}
