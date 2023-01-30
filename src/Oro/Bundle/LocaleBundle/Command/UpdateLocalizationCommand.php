<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
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

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $languageCode = (string)$input->getOption('language');
        $formattingCode = (string)$input->getOption('formatting-code');

        if ($languageCode === Translator::DEFAULT_LOCALE && $formattingCode === Translator::DEFAULT_LOCALE) {
            return 0;
        }

        /** @var LocalizationRepository $localizationRepository */
        $localizationRepository = $this->getManager(Localization::class)->getRepository(Localization::class);
        $localization = $localizationRepository
            ->findOneByLanguageCodeAndFormattingCode(Translator::DEFAULT_LOCALE, Translator::DEFAULT_LOCALE);

        // Try to fetch localization for en_US default formatting code. Should be removed in scope of #BAP-19605
        if (!$localization) {
            $localization = $localizationRepository
                ->findOneByLanguageCodeAndFormattingCode(Translator::DEFAULT_LOCALE, 'en_US');
        }

        if ($localization) {
            $language = $localization->getLanguage();
            if ($language->getCode() !== $languageCode) {
                $language = $this->createLanguage($language, $languageCode);
            }

            $this->updateLocalization($localization, $language, $formattingCode);
        } else {
            throw new \RuntimeException('Default localization not found');
        }

        return 0;
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
