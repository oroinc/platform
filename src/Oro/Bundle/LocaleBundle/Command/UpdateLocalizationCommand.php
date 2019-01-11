<?php

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Intl;

/**
 * This command for internal use only!
 *
 * It update the localization with "en" language and "en" formatting code to localization passed as non-required options
 * "language" and "formatting-code" to `oro:install` command.
 * Additional language will be created if non-required option "language" will be passed to `oro:install` command.
 *
 * Example:
 *  `oro:install --language=de --formatting-code=de_DE`
 *  will change language to "de" language and change formatting code to de_DE for default localization.
 */
class UpdateLocalizationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:localization:update')
            ->setHidden(true)
            ->setDescription("This command for internal use only!.\nUpdate default en localization.")
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
                'Language',
                Translator::DEFAULT_LOCALE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $languageCode = (string)$input->getOption('language');
        $formattingCode = (string)$input->getOption('formatting-code');

        if ($languageCode === Translator::DEFAULT_LOCALE && $formattingCode === Translator::DEFAULT_LOCALE) {
            return;
        }

        $localization = $this->getManager(Localization::class)
            ->getRepository(Localization::class)
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
