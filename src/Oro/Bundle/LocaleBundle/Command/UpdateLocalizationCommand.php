<?php

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
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
 * Replaces "en" language and "en" formatting code in the default localization.
 *
 * Example:
 *  `oro:install --language=de --formatting-code=de_DE`
 *  will change "Language" value to "de" and "Formatting code" value to "de_DE" for the default localization.
 */
class UpdateLocalizationCommand extends Command
{
    public const OPTION_FORMATTING_CODE = 'formatting-code';
    public const OPTION_LANGUAGE = 'language';

    /** @var string */
    protected static $defaultName = 'oro:localization:update';

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = <<<EOD
This is a hidden command for internal use only (it's called from the installer).
Do not run it directly!

It replaces "en" in "Language" and "Formatting code" options of the default
localization with the new values passed as optional "language" and
"formatting-code" options to `oro:install` command.
It will also create a new language entity for the "language" option value,
if such language does not exist yet.
EOD;

        $this
            ->setHidden(true)
            ->setDescription($description)
            ->addOption(
                self::OPTION_FORMATTING_CODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Formatting code',
                Translator::DEFAULT_LOCALE
            )
            ->addOption(
                self::OPTION_LANGUAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Language',
                Translator::DEFAULT_LOCALE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $languageCode = (string)$input->getOption(self::OPTION_LANGUAGE);
        $formattingCode = (string)$input->getOption(self::OPTION_FORMATTING_CODE);

        if ($languageCode === Translator::DEFAULT_LOCALE && $formattingCode === Translator::DEFAULT_LOCALE) {
            return;
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
    }

    /**
     * @param Language $defaultLanguage
     * @param string $languageCode
     * @return Language
     */
    private function createLanguage(Language $defaultLanguage, string $languageCode): Language
    {
        /** @var EntityManager $em */
        $em = $this->getManager(Language::class);

        $language = new Language();
        $language->setCode($languageCode)
            ->setEnabled(true)
            ->setOwner($defaultLanguage->getOwner())
            ->setOrganization($defaultLanguage->getOrganization());

        $em->persist($language);
        $em->flush($language);

        return $language;
    }

    /**
     * @param Localization $localization
     * @param Language $language
     * @param string $formattingCode
     */
    private function updateLocalization(Localization $localization, Language $language, string $formattingCode): void
    {
        $title = Locales::getName($formattingCode, $language->getCode());

        $localization->setFormattingCode($formattingCode)
            ->setLanguage($language)
            ->setName($title)
            ->setDefaultTitle($title);

        $this->getManager(Localization::class)->flush();
    }

    /**
     * @param string $className
     *
     * @return EntityManager
     */
    private function getManager(string $className): EntityManager
    {
        return $this->doctrine->getManagerForClass($className);
    }
}
