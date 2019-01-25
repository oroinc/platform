<?php

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Intl;

/**
 * Replaces "en" language and "en" formatting code in the default localization.
 *
 * Example:
 *  `oro:install --language=de --formatting-code=de_DE`
 *  will change "Language" value to "de" and "Formatting code" value to "de_DE" for the default localization.
 */
class UpdateLocalizationCommand extends ContainerAwareCommand
{
    public const NAME = 'oro:localization:update';

    public const OPTION_FORMATTING_CODE = 'formatting-code';
    public const OPTION_LANGUAGE = 'language';

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

        $this->setName(self::NAME)
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
        $title = Intl::getLocaleBundle()->getLocaleName($formattingCode, $language->getCode());

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
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }
}
